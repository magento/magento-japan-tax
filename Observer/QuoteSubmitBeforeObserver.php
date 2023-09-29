<?php

namespace Magentoj\JapaneseConsumptionTax\Observer;

use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;
use Magentoj\JapaneseConsumptionTax\Api\Data\JctTotalsInterfaceFactory;

class QuoteSubmitBeforeObserver implements ObserverInterface
{
    private JctTotalsInterfaceFactory $jctTotalsInterfaceFactory;

    /**
     * QuoteSubmitBeforeObserver constructor.
     * @param JctTotalsInterfaceFactory $jctTotalsInterfaceFactory
     */
    public function __construct(
        JctTotalsInterfaceFactory $jctTotalsInterfaceFactory
    ) {
        $this->jctTotalsInterfaceFactory = $jctTotalsInterfaceFactory;
    }

    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $quote = $observer->getEvent()->getQuote();

        $address = $quote->isVirtual() ? $quote->getBillingAddress() : $quote->getShippingAddress();

        $extensionAttributes = $order->getExtensionAttributes();
        $extensionAttributes->setJctTotals($address->getJctTotals());
        $order->setExtensionAttributes($extensionAttributes);
    }
}
