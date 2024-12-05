<?php
declare(strict_types=1);

namespace Cawl\RedirectPayment\Service\CreateHostedCheckoutRequest;

use Magento\Framework\Event\ManagerInterface;
use Magento\Quote\Api\Data\CartInterface;
use OnlinePayments\Sdk\Domain\CardPaymentMethodSpecificInput;
use OnlinePayments\Sdk\Domain\CardPaymentMethodSpecificInputFactory;
use Cawl\HostedCheckout\Api\TokenManagerInterface;
use Cawl\PaymentCore\Api\Data\PaymentProductsDetailsInterface;
use Cawl\PaymentCore\Api\Service\CreateRequest\ThreeDSecureDataBuilderInterface;
use Cawl\RedirectPayment\Gateway\Config\Config;
use Cawl\RedirectPayment\Ui\ConfigProvider;
use Cawl\RedirectPayment\WebApi\RedirectManagement;

class CardPaymentMethodSIDBuilder
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var CardPaymentMethodSpecificInputFactory
     */
    private $cardPaymentMethodSpecificInputFactory;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var ThreeDSecureDataBuilderInterface
     */
    private $threeDSecureDataBuilder;

    /**
     * @var int[]
     */
    private $alwaysSaleProductIds;

    /**
     * @var TokenManagerInterface
     */
    private $tokenManager;

    public function __construct(
        Config $config,
        CardPaymentMethodSpecificInputFactory $cardPaymentMethodSpecificInputFactory,
        ManagerInterface $eventManager,
        ThreeDSecureDataBuilderInterface $threeDSecureDataBuilder,
        TokenManagerInterface $tokenManager,
        array $alwaysSaleProductIds = []
    ) {
        $this->config = $config;
        $this->cardPaymentMethodSpecificInputFactory = $cardPaymentMethodSpecificInputFactory;
        $this->eventManager = $eventManager;
        $this->threeDSecureDataBuilder = $threeDSecureDataBuilder;
        $this->alwaysSaleProductIds = $alwaysSaleProductIds;
        $this->tokenManager = $tokenManager;
    }

    public function build(CartInterface $quote): ?CardPaymentMethodSpecificInput
    {
        $storeId = (int)$quote->getStoreId();
        /** @var CardPaymentMethodSpecificInput $cardPaymentMethodSpecificInput */
        $cardPaymentMethodSpecificInput = $this->cardPaymentMethodSpecificInputFactory->create();

        $payProductId = (int)$quote->getPayment()->getAdditionalInformation(RedirectManagement::PAYMENT_PRODUCT_ID);
        if ($payProductId) {
            $cardPaymentMethodSpecificInput->setPaymentProductId($payProductId);
            $this->checkIntersolveGiftCards($cardPaymentMethodSpecificInput, $payProductId, $storeId);
        }

        $cardPaymentMethodSpecificInput->setAuthorizationMode(
            $this->getAuthorizationMode((int) $payProductId, $storeId)
        );

        $cardPaymentMethodSpecificInput->setThreeDSecure($this->threeDSecureDataBuilder->build($quote));

        if ($token = $this->tokenManager->getToken($quote)) {
            if ($this->tokenManager->isSepaToken($token)) {
                return null;
            }

            $cardPaymentMethodSpecificInput->setToken($token->getGatewayToken());
        }

        $args = ['quote' => $quote, 'card_payment_method_specific_input' => $cardPaymentMethodSpecificInput];
        $this->eventManager->dispatch(ConfigProvider::CODE . '_card_payment_method_specific_input_builder', $args);

        return $cardPaymentMethodSpecificInput;
    }

    private function getAuthorizationMode(int $payProductId, int $storeId): string
    {
        if (in_array($payProductId, $this->alwaysSaleProductIds, true)) {
            return Config::AUTHORIZATION_MODE_SALE;
        }

        return $this->config->getAuthorizationMode($storeId);
    }

    private function checkIntersolveGiftCards(
        CardPaymentMethodSpecificInput $cardPaymentMethodSpecificInput,
        int $payProductId,
        int $storeId
    ): void {
        if ($payProductId !== PaymentProductsDetailsInterface::INTERSOLVE_PRODUCT_ID) {
            return;
        }

        $giftCards = $this->config->getIntersolveGiftCards($storeId);
        if (!empty($giftCards)) {
            if (count($giftCards) === 1) {
                $cardPaymentMethodSpecificInput->setPaymentProductId((int)$giftCards[0]);
            } else {
                $cardPaymentMethodSpecificInput->setPaymentProductId(null);
            }
        }
    }
}
