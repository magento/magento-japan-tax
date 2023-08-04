<?php
namespace Japan\Tax\Observer;

use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;

class QuoteSubmitBeforeObserver implements ObserverInterface
{
    public function execute(Observer $observer) {
        $order = $observer->getEvent()->getOrder();
        $quote = $observer->getEvent()->getQuote();

        if ($quote->isVirtual()) {
            $address = $quote->getBillingAddress();
        } else {
            $address = $quote->getShippingAddress();
        }

        $order->setSubtotalExclJct10($address->getSubtotalExclJct10());
        $order->setBaseSubtotalExclJct10($address->getBaseSubtotalExclJct10());
        $order->setSubtotalInclJct10($address->getSubtotalInclJct10());
        $order->setBaseSubtotalInclJct10($address->getBaseSubtotalInclJct10());
        $order->setSubtotalExclJct8($address->getSubtotalExclJct8());
        $order->setBaseSubtotalExclJct8($address->getBaseSubtotalExclJct8());
        $order->setSubtotalInclJct8($address->getSubtotalInclJct8());
        $order->setBaseSubtotalInclJct8($address->getBaseSubtotalInclJct8());
        $order->setJct10Amount($address->getJct10Amount());
        $order->setBaseJct10Amount($address->getBaseJct10Amount());
        $order->setJct8Amount($address->getJct8Amount());
        $order->setBaseJct8Amount($address->getBaseJct8Amount());
    }
}
