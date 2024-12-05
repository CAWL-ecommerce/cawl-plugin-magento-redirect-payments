<?php
declare(strict_types=1);

namespace Cawl\RedirectPayment\Test\Integration\Settings;

use Magento\Quote\Api\Data\CartInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Cawl\RedirectPayment\Service\CreateHostedCheckoutRequest\CardPaymentMethodSIDBuilder;
use Cawl\RedirectPayment\Ui\ConfigProvider;
use Cawl\PaymentCore\Api\Data\PaymentProductsDetailsInterface;
use Cawl\PaymentCore\Api\QuoteResourceInterface;
use Cawl\PaymentCore\Api\Test\Infrastructure\ServiceStubSwitcherInterface;

/**
 * Test case for configurations:
 * "Enable 3-D Secure Authentication" and "Enforce Strong Customer Authentication for Every Payment"
 */
class ThreeDSecureTest extends TestCase
{
    /**
     * @var CardPaymentMethodSIDBuilder
     */
    private $cardPaymentMethodSIDBuilder;

    /**
     * @var QuoteResourceInterface
     */
    private $quoteExtendedRepository;

    public function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->cardPaymentMethodSIDBuilder = $objectManager->get(CardPaymentMethodSIDBuilder::class);
        $this->quoteExtendedRepository = $objectManager->get(QuoteResourceInterface::class);
        $objectManager->get(ServiceStubSwitcherInterface::class)->setEnabled(true);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture default/currency/options/allow EUR
     * @magentoConfigFixture default/currency/options/base EUR
     * @magentoConfigFixture default/currency/options/default EUR
     * @magentoConfigFixture current_store payment/worldline_hosted_checkout_1/active 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/active 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/payment_action authorize
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/authorization_mode final
     * @magentoConfigFixture current_store worldline_payment/general_settings/enable_3d 1
     * @magentoConfigFixture current_store worldline_payment/general_settings/enforce_authentication 1
     * @magentoConfigFixture current_store worldline_connection/webhook/key test-X-Gcs-Keyid
     * @magentoConfigFixture current_store worldline_connection/webhook/secret_key test-X-Gcs-Signature
     */
    public function testThreeDSecure(): void
    {
        $quote = $this->getQuote();
        $cardPaymentMethodSpecificInput = $this->cardPaymentMethodSIDBuilder->build($quote);

        $this->assertNotFalse(
            strpos(
                $cardPaymentMethodSpecificInput->getThreeDSecure()->getRedirectionData()->getReturnUrl(),
                'wl_hostedcheckout/returns/returnUrl'
            )
        );

        $this->assertFalse($cardPaymentMethodSpecificInput->getThreeDSecure()->getSkipAuthentication());
        $this->assertEquals(
            'challenge-required',
            $cardPaymentMethodSpecificInput->getThreeDSecure()->getChallengeIndicator()
        );
    }

    private function getQuote(): CartInterface
    {
        $quote = $this->quoteExtendedRepository->getQuoteByReservedOrderId('test01');
        $quote->setReservedOrderId('test02');
        $quote->getPayment()->setMethod(
            ConfigProvider::CODE . '_' . PaymentProductsDetailsInterface::VISA_PRODUCT_ID
        );
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
