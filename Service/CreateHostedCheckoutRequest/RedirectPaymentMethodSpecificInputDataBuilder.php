<?php
declare(strict_types=1);

namespace Cawl\RedirectPayment\Service\CreateHostedCheckoutRequest;

use Magento\Framework\Event\ManagerInterface;
use Magento\Quote\Api\Data\CartInterface;
use OnlinePayments\Sdk\Domain\RedirectPaymentMethodSpecificInput;
use OnlinePayments\Sdk\Domain\RedirectPaymentMethodSpecificInputFactory;
use OnlinePayments\Sdk\Domain\RedirectPaymentProduct5408SpecificInputFactory;
use Cawl\PaymentCore\Api\Data\PaymentProductsDetailsInterface;
use Cawl\RedirectPayment\Gateway\Config\Config;
use Cawl\RedirectPayment\Ui\ConfigProvider;
use Cawl\RedirectPayment\WebApi\RedirectManagement;

class RedirectPaymentMethodSpecificInputDataBuilder
{
    public const RP_METHOD_SPECIFIC_INPUT = 'redirect_payment_method_specific_input';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var RedirectPaymentMethodSpecificInputFactory
     */
    private $redirectPaymentMethodSpecificInputFactory;

    /**
     * @var RedirectPaymentProduct5408SpecificInputFactory
     */
    private $paymentProduct5408SIFactory;

    public function __construct(
        Config $config,
        ManagerInterface $eventManager,
        RedirectPaymentMethodSpecificInputFactory $redirectPaymentMethodSpecificInputFactory,
        RedirectPaymentProduct5408SpecificInputFactory $paymentProduct5408SIFactory
    ) {
        $this->config = $config;
        $this->eventManager = $eventManager;
        $this->redirectPaymentMethodSpecificInputFactory = $redirectPaymentMethodSpecificInputFactory;
        $this->paymentProduct5408SIFactory = $paymentProduct5408SIFactory;
    }

    public function build(CartInterface $quote): RedirectPaymentMethodSpecificInput
    {
        $storeId = (int)$quote->getStoreId();
        /** @var RedirectPaymentMethodSpecificInput $redirectPaymentMethodSpecificInput */
        $redirectPaymentMethodSpecificInput = $this->redirectPaymentMethodSpecificInputFactory->create();
        $payProductId = $quote->getPayment()->getAdditionalInformation(RedirectManagement::PAYMENT_PRODUCT_ID);
        $authMode = $this->config->getAuthorizationMode();
        if ($payProductId && ($payProductId === PaymentProductsDetailsInterface::MEALVOUCHERS_PRODUCT_ID
                || $payProductId === PaymentProductsDetailsInterface::CHEQUE_VACANCES_CONNECT_PRODUCT_ID)
        ) {
            $redirectPaymentMethodSpecificInput->setRequiresApproval(false);
        } else {
            $redirectPaymentMethodSpecificInput->setRequiresApproval($authMode !== Config::AUTHORIZATION_MODE_SALE);
        }
        $redirectPaymentMethodSpecificInput->setPaymentOption(
            $this->config->getOneyPaymentOption($storeId)
        );
        if ($payProductId) {
            $redirectPaymentMethodSpecificInput->setPaymentProductId((int)$payProductId);
        }

        $paymentProduct5408SI = $this->paymentProduct5408SIFactory->create();
        $paymentProduct5408SI->setInstantPaymentOnly($this->config->getBankTransferMode($storeId));
        $redirectPaymentMethodSpecificInput->setPaymentProduct5408SpecificInput($paymentProduct5408SI);

        $args = ['quote' => $quote, self::RP_METHOD_SPECIFIC_INPUT => $redirectPaymentMethodSpecificInput];
        $this->eventManager->dispatch(ConfigProvider::CODE . '_redirect_payment_method_specific_input_builder', $args);

        return $redirectPaymentMethodSpecificInput;
    }
}
