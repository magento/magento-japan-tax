<?php
/**
 * This file is part of Japanese Consumption Tax Extension For Magento2 the  project.
 *
 * Copyright (c) 2023 Adobe (or other copyright holders)
 *
 * For the full copyright and license information, please view the OSL-3.0
 * license that is bundled with this source code in the file LICENSE, or
 * at https://opensource.org/licenses/OSL-3.0
 */
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
