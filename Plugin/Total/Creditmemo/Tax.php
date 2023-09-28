<?php
namespace Magentoj\JapaneseConsumptionTax\Plugin\Total\Creditmemo;

use Magentoj\JapaneseConsumptionTax\Model\Calculation\OrderItemAdapter;

class Tax
{
    use \Magentoj\JapaneseConsumptionTax\Plugin\Total\JctTotalTrait;

    public const JCT_10_PERCENT = 10;
    public const JCT_8_PERCENT = 8;
    
    /**
     * @var \Magento\Sales\Model\Order\Creditmemo\ItemFactory
     */
    protected $creditmemoItemFactory;

    /**
     * @var \Magentoj\JapaneseConsumptionTax\Model\Calculation\JctTaxCalculator
     */
    private $jctTaxCalculator;

    public function __construct(
        \Magento\Sales\Model\Order\Creditmemo\ItemFactory $creditmemoItemFactory,
        \Magentoj\JapaneseConsumptionTax\Model\Calculation\JctTaxCalculator $jctTaxCalculator,
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
