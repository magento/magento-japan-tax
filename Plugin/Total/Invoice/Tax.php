<?php
namespace Magentoj\JapaneseConsumptionTax\Plugin\Total\Invoice;

use Magentoj\JapaneseConsumptionTax\Model\Calculation\OrderItemAdapter;

class Tax extends \Magento\Sales\Model\Order\Invoice\Total\Tax
{
    use \Magentoj\JapaneseConsumptionTax\Plugin\Total\JctTotalTrait;

    public const JCT_10_PERCENT = 10;
    public const JCT_8_PERCENT = 8;

    /**
     * @var \Magento\Sales\Model\Order\Invoice\ItemFactory
     */
    protected $invoiceItemFactory;

    /**
     * @var \Magentoj\JapaneseConsumptionTax\Model\Calculation\JctTaxCalculator
     */
    private $jctTaxCalculator;

    public function __construct(
        \Magento\Sales\Model\Order\Invoice\ItemFactory $invoiceItemFactory,
        \Magentoj\JapaneseConsumptionTax\Model\Calculation\JctTaxCalculator $jctTaxCalculator,
    ) {
        $this->invoiceItemFactory = $invoiceItemFactory;
        $this->jctTaxCalculator = $jctTaxCalculator;
        parent::__construct();
    }

    public function afterCollect(
        \Magento\Sales\Model\Order\Invoice\Total\Tax $subject,
        \Magento\Sales\Model\Order\Invoice\Total\Tax $result,
        \Magento\Sales\Model\Order\Invoice $invoice,
    ) {
        $order = $invoice->getOrder();
        $isTaxIncluded = $order->getIsTaxIncluded();

        $aggregate = [];
        foreach ($invoice->getAllItems() as $item) {
            if ($item->getOrderItem()->isDummy() || $item->getQty() <= 0) {
                continue;
            }

            $aggregate = $this->updateItemAggregate(
                $aggregate,
                intval($item->getTaxPercent()),
                new OrderItemAdapter($item)
            );
        }

        if ($this->_canIncludeShipping($invoice)) {
            $shippingItem = $this->invoiceItemFactory->create();
            $shippingItem
                ->setProductType('shipping')
                ->setSku('shipping')
                ->setPrice($isTaxIncluded ?
                    $invoice->getShippingInclTax() : $invoice->getShippingAmount())
                ->setQty(1);
            $aggregate = $this->updateItemAggregate(
                $aggregate,
                self::JCT_10_PERCENT,
                new OrderItemAdapter($shippingItem),
            );
        }

        $blocks = [];
        if ($isTaxIncluded) {
            foreach ($aggregate as $data) {
                $blocks[] = $this->jctTaxCalculator->calculateWithTaxInPrice(
                    $data["items"],
                    $data["taxRate"],
                    $data["storeRate"],
                    $order->getStoreId(),
                    $data["appliedRates"],
                    $invoice->getOrderCurrencyCode()
                );
            }
        } else {
            foreach ($aggregate as $data) {
                $blocks[] = $this->jctTaxCalculator->calculateWithTaxNotInPrice(
                    $data["items"],
                    $data["taxRate"],
                    $data["storeRate"],
                    $data["appliedRates"],
                    $invoice->getOrderCurrencyCode()
                );
            }
        }

        $totalTax = 0;
        foreach ($blocks as $block) {
            $totalTax += $block->getTax();

            $taxPercent = (int) $block->getTaxPercent();
            if ($taxPercent === self::JCT_10_PERCENT) {
                $invoice->setSubtotalExclJct10($this->calculateSubtotalExclTax($block));
                $invoice->setSubtotalInclJct10($this->calculateSubtotalInclTax($block));
                $invoice->setJct10Amount($block->getTax());
            } elseif ($taxPercent === self::JCT_8_PERCENT) {
                $invoice->setSubtotalExclJct8($this->calculateSubtotalExclTax($block));
                $invoice->setSubtotalInclJct8($this->calculateSubtotalInclTax($block));
                $invoice->setJct8Amount($block->getTax());
            }
            $invoice->setIsTaxIncluded($block->getIsTaxIncluded());
        }

        $invoice->setTaxAmount($totalTax);

        return $result;
    }
}
