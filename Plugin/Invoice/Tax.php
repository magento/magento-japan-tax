<?php
namespace Japan\Tax\Plugin\Invoice;

use Japan\Tax\Model\Calculation\OrderItemAdapter;

class Tax extends \Magento\Sales\Model\Order\Invoice\Total\Tax
{
    public const JCT_10_PERCENT = 10;
    public const JCT_8_PERCENT = 8;

    /**
     * @var \Magento\Sales\Model\Order\Invoice\ItemFactory
     */
    protected $invoiceItemFactory;

    /**
     * @var \Japan\Tax\Model\Calculation\JctTaxCalculator
     */
    private $jctTaxCalculator;

    public function __construct(
        \Magento\Sales\Model\Order\Invoice\ItemFactory $invoiceItemFactory,
        \Japan\Tax\Model\Calculation\JctTaxCalculator $jctTaxCalculator,
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
            $aggregate = $this->updateAggregate($item, $aggregate);
        }

        if ($this->_canIncludeShipping($invoice)) {
            $shippingItem = $this->getShippingItem($invoice, $isTaxIncluded);
            $aggregate = $this->updateAggregate($shippingItem, $aggregate);
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

    private function updateAggregate(
        \Magento\Sales\Model\Order\Invoice\Item $item,
        array $aggregate,
    ) {
        $rate = intval($item->getTaxPercent());
        if (!isset($aggregate[$rate])) {
            $aggregate[$rate] = [
                "appliedRates" => [
                    [
                        "rates" => [],
                        "percent" => $rate,
                        "id" => "Japan_Tax::$rate",
                    ],
                ],
                "taxRate" => $rate,
                "storeRate" => 0,
                "items" => [],
            ];
        }
        $aggregate[$rate]["items"][] = new OrderItemAdapter($item);

        return $aggregate;
    }

    private function getShippingItem(
        \Magento\Sales\Model\Order\Invoice $invoice,
        $isTaxIncluded,
    ) {
        $price = $isTaxIncluded ?
            $invoice->getShippingInclTax() : $invoice->getShippingAmount();

        $shippingItem = $this->invoiceItemFactory->create();
        $shippingItem
            ->setProductType('shipping')
            ->setSku('shipping')
            ->setPrice($price)
            ->setQty(1)
            ->setTaxPercent(self::JCT_10_PERCENT);

        return $shippingItem;
    }

    private function calculateSubtotalExclTax(
        \Japan\Tax\Api\Data\InvoiceTaxBlockInterface $block
    ) {
        return $block->getIsTaxIncluded() ?
            $block->getTotal() - $block->getDiscountAmount() + $block->getDiscountTaxCompensationAmount() :
            $block->getTotal() - $block->getDiscountAmount();
    }

    private function calculateSubtotalInclTax(
        \Japan\Tax\Api\Data\InvoiceTaxBlockInterface $block
    ) {
        return $block->getIsTaxIncluded() ?
            $block->getTotalInclTax() - $block->getDiscountAmount() :
            $block->getTotal() + $block->getTax() - $block->getDiscountAmount();
    }
}
