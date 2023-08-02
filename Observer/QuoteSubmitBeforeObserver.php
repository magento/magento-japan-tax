<?php
namespace Japan\Tax\Observer;

use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;

class QuoteSubmitBeforeObserver implements ObserverInterface
{
    public function execute(Observer $observer) {
        $order = $observer->getEvent()->getOrder();
        $quote = $observer->getEvent()->getQuote();

        $invoiceTax = $quote->getExtensionAttributes()->getInvoiceTax();

        $subtotalsExclTaxByRate = [10 => 0, 8 => 0];
        $subtotalsInclTaxByRate = [10 => 0, 8 => 0];
        $taxesByRate = [10 => 0, 8 => 0];

        if ($invoiceTax) {
            foreach ($invoiceTax->getBlocks() as $block) {
                $rate = (int)$block->getTaxPercent();
                if (isset($subtotalsExclTaxByRate[$rate]) && 
                    isset($subtotalsInclTaxByRate[$rate]) && 
                    isset($taxesByRate[$rate])
                ) {
                    $subtotalsExclTaxByRate[$rate] += $block->getTotal();
                    $subtotalsInclTaxByRate[$rate] += $block->getTotalInclTax();
                    $taxesByRate[$rate] += $block->getTax();
                }
            }
        }
        
        $order->setSubtotalExclJct($subtotalsExclTaxByRate[10]);
        $order->setSubtotalExclReducedJct($subtotalsExclTaxByRate[8]);

        $order->setSubtotalInclJct($subtotalsInclTaxByRate[10]);
        $order->setSubtotalInclReducedJct($subtotalsInclTaxByRate[8]);

        $order->setJctAmount($taxesByRate[10]);
        $order->setReducedJctAmount($taxesByRate[8]);
    }
}
