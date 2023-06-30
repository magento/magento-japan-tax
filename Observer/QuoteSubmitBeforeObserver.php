<?php
namespace Japan\Tax\Observer;
 
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

// use Magento\Tax\Model\Calculation;
// use Magento\Tax\Model\Calculation\Rate;

class QuoteSubmitBeforeObserver implements ObserverInterface
{
    // protected $calculation;
    // protected $taxRate;

    // public function __construct(
    //     Calculation $calculation,
    //     Rate $taxRate,
    // ) {
    //     $this->calculation = $calculation;
    //     $this->taxRate = $taxRate;
    // }

    public function execute(Observer $observer)
    {
        file_put_contents('/home/nozomio/dev/magento-cloud-ap-4/QuoteSubmitBeforeObserver.txt', 'QuoteSubmitBeforeObserver is called');
        $order = $observer->getEvent()->getOrder();
        $quote = $observer->getEvent()->getQuote();

    //     $subTotalByRate = [];
    //     foreach ($quote->getAllItems() as $item) {
    //         $taxRateId = $item->getTaxRateId();
    //         $tax = $this->taxRate->load($taxRateId);
    //         $taxRate = $tax->getRate();
 
    //         if (!isset($subTotalByRate[$taxRate])) {
    //             $subTotalByRate[$taxRate] = 0;
    //         }
    //         $subTotalByRate[$taxRate] += $taxRate->getSubTotal();
    //     }
    //     $taxesByRate = [];
    //     $totalTaxes = 0;
    //     $TotalSubtotal = 0;
    //     foreach ($subTotalByRate as $rate => $subTotal) {
    //         $taxesByRate[$rate] = $subTotal * $rate;
    //         $totalTaxes += $subTotal * $rate;
    //         $TotalSubtotal += $subTotal;
    //     }

    //     // Set the order's tax amount and base tax amount
    //     $order->setTaxAmount($totalTaxes);
    //     $order->setBaseTaxAmount($TotalSubtotal);
        
    //     // Set the tax breakdown by tax rate as additional data on the order
    //     $order->setTaxBreakdownByRate($taxesByRate);
    }
}
