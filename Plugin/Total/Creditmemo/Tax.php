<?php
namespace Japan\Tax\Plugin\Total\Creditmemo;

use Japan\Tax\Model\Calculation\OrderItemAdapter;

class Tax
{
    use \Japan\Tax\Plugin\Total\JctTotal;

    public const JCT_10_PERCENT = 10;
    public const JCT_8_PERCENT = 8;
    
    /**
     * @var \Magento\Sales\Model\Order\Creditmemo\ItemFactory
     */
    protected $creditmemoItemFactory;

    /**
     * @var \Japan\Tax\Model\Calculation\JctTaxCalculator
     */
    private $jctTaxCalculator;

    public function __construct(
        \Magento\Sales\Model\Order\Creditmemo\ItemFactory $creditmemoItemFactory,
        \Japan\Tax\Model\Calculation\JctTaxCalculator $jctTaxCalculator,
    ) {
        $this->creditmemoItemFactory = $creditmemoItemFactory;
        $this->jctTaxCalculator = $jctTaxCalculator;
    }

    public function afterCollect(
        \Magento\Sales\Model\Order\Creditmemo\Total\Tax $subject,
        \Magento\Sales\Model\Order\Creditmemo\Total\Tax $result,
        \Magento\Sales\Model\Order\Creditmemo $creditmemo,
    ) {
        $order = $creditmemo->getOrder();
        $isTaxIncluded = $order->getIsTaxIncluded();

        $aggregate = [];
        foreach ($creditmemo->getAllItems() as $item) {
            if ($item->getOrderItem()->isDummy() || $item->getQty() <= 0) {
                continue;
            }
            $aggregate = $this->updateItemAggregate(
                $aggregate,
                intval($item->getTaxPercent()),
                new OrderItemAdapter($item)
            );
        }

        $shippingItem = $this->creditmemoItemFactory->create();
        $shippingItem
            ->setProductType('shipping')
            ->setSku('shipping')
            ->setPrice($isTaxIncluded ?
                    $creditmemo->getShippingInclTax() : $creditmemo->getShippingAmount())
            ->setQty(1);
        $aggregate = $this->updateItemAggregate(
            $aggregate,
            self::JCT_10_PERCENT,
            new OrderItemAdapter($shippingItem),
        );

        $blocks = [];
        if ($isTaxIncluded) {
            foreach ($aggregate as $data) {
                $blocks[] = $this->jctTaxCalculator->calculateWithTaxInPrice(
                    $data["items"],
                    $data["taxRate"],
                    $data["storeRate"],
                    $order->getStoreId(),
                    $data["appliedRates"],
                    $creditmemo->getOrderCurrencyCode()
                );
            }
        } else {
            foreach ($aggregate as $data) {
                $blocks[] = $this->jctTaxCalculator->calculateWithTaxNotInPrice(
                    $data["items"],
                    $data["taxRate"],
                    $data["storeRate"],
                    $data["appliedRates"],
                    $creditmemo->getOrderCurrencyCode()
                );
            }
        }

        $totalTax = 0;
        foreach ($blocks as $block) {
            $totalTax += $block->getTax();

            $taxPercent = (int) $block->getTaxPercent();
            if ($taxPercent === self::JCT_10_PERCENT) {
                $creditmemo->setSubtotalExclJct10($this->calculateSubtotalExclTax($block));
                $creditmemo->setSubtotalInclJct10($this->calculateSubtotalInclTax($block));
                $creditmemo->setJct10Amount($block->getTax());
            } elseif ($taxPercent === self::JCT_8_PERCENT) {
                $creditmemo->setSubtotalExclJct8($this->calculateSubtotalExclTax($block));
                $creditmemo->setSubtotalInclJct8($this->calculateSubtotalInclTax($block));
                $creditmemo->setJct8Amount($block->getTax());
            }
            $creditmemo->setIsTaxIncluded($block->getIsTaxIncluded());
        }

        $creditmemo->setTaxAmount($totalTax);

        return $result;
    }
}
