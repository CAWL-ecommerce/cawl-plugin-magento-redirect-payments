<?php
declare(strict_types=1);

namespace Cawl\RedirectPayment\Test\Integration\Payment;

use Magento\Quote\Api\Data\CartInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Cawl\PaymentCore\Api\Data\PaymentProductsDetailsInterface;
use Cawl\PaymentCore\Api\FraudRepositoryInterface;
use Cawl\PaymentCore\Api\PaymentRepositoryInterface;
use Cawl\PaymentCore\Api\QuoteResourceInterface;
use Cawl\PaymentCore\Api\Test\Infrastructure\ServiceStubSwitcherInterface;
use Cawl\PaymentCore\Api\Test\Infrastructure\WebhookStubSenderInterface;
use Cawl\PaymentCore\Api\TransactionRepositoryInterface;
use Cawl\PaymentCore\Infrastructure\StubData\Webhook\Authorization;
use Cawl\RedirectPayment\Ui\ConfigProvider;

/**
 * Test case about payment information in order
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OrderPaymentInfoTest extends TestCase
{
    /**
     * @var  WebhookStubSenderInterface
     */
    private $webhookStubSender;

    /**
     * @var QuoteResourceInterface
     */
    private $quoteExtendedRepository;

    /**
     * @var PaymentRepositoryInterface
     */
    private $paymentRepository;

    /**
     * @var TransactionRepositoryInterface
     */
    private $transactionRepository;

    /**
     * @var FraudRepositoryInterface
     */
    private $fraudRepository;

    public function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->webhookStubSender = $objectManager->get(WebhookStubSenderInterface::class);
        $this->quoteExtendedRepository = $objectManager->get(QuoteResourceInterface::class);
        $this->paymentRepository = $objectManager->get(PaymentRepositoryInterface::class);
        $this->transactionRepository = $objectManager->get(TransactionRepositoryInterface::class);
        $this->fraudRepository = $objectManager->get(FraudRepositoryInterface::class);
        $objectManager->get(ServiceStubSwitcherInterface::class)->setEnabled(true);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture default/currency/options/allow EUR
     * @magentoConfigFixture default/currency/options/base EUR
     * @magentoConfigFixture default/currency/options/default EUR
     * @magentoConfigFixture current_store payment/worldline_redirect_payment_1/active 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/active 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/payment_action authorize_capture
     * @magentoConfigFixture current_store worldline_connection/webhook/key test-X-Gcs-Keyid
     * @magentoConfigFixture current_store worldline_connection/webhook/secret_key test-X-Gcs-Signature
     */
    public function testOrderPaymentInfo(): void
    {
        $quote = $this->getQuote();

        // send the webhook and place the order
        $result = $this->webhookStubSender->sendWebhook(Authorization::getData($quote->getReservedOrderId()));

        // validate controller result
        $reflectedResult = new \ReflectionObject($result);
        $jsonProperty = $reflectedResult->getProperty('json');
        $jsonProperty->setAccessible(true);
        $this->assertEquals('{"messages":[],"error":false}', $jsonProperty->getValue($result));

        // validate payment info
        $payment = $this->paymentRepository->get('test01');
        $this->assertEquals('3254564310_0', $payment->getPaymentId());

        $transaction = $this->transactionRepository->getLastTransaction('test01');
        $this->assertEquals('3254564310_0', $transaction->getTransactionId());

        // validate fraud info
        $fraudInfo = $this->fraudRepository->getByIncrementId('test01');
        $this->assertEquals('accepted', $fraudInfo->getResult());
        $this->assertEquals('issuer', $fraudInfo->getLiability());
        $this->assertEquals('Y', $fraudInfo->getAuthenticationStatus());
    }

    private function getQuote(): CartInterface
    {
        $quote = $this->quoteExtendedRepository->getQuoteByReservedOrderId('test01');
        $quote->getPayment()->setMethod(ConfigProvider::CODE . '_' . PaymentProductsDetailsInterface::VISA_PRODUCT_ID);
        $quote->getShippingAddress()->setShippingMethod('flatrate_flatrate');
        $quote->getShippingAddress()->setCollectShippingRates(true);
        $quote->getShippingAddress()->collectShippingRates();
        $quote->setCustomerEmail('example@worldline.com');
        $quote->getPayment()->setAdditionalInformation('payment_id', '3254564310_0');
        $quote->getPayment()->setAdditionalInformation('token_id', 'test');
        $quote->collectTotals();
        $quote->save();

        return $quote;
    }
}
