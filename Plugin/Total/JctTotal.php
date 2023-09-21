<?php
namespace Japan\Tax\Plugin\Total;

trait JctTotal
{
    /**
     * Calculates the subtotal excluding tax based on the given invoice tax block.
     *
     * @param \Japan\Tax\Api\Data\InvoiceTaxBlockInterface $block The invoice tax block data.
     *
     * @return float
     */
    private function calculateSubtotalExclTax(
        \Japan\Tax\Api\Data\InvoiceTaxBlockInterface $block
    ) {
        return $block->getIsTaxIncluded() ?
            $block->getTotal() - $block->getDiscountAmount() + $block->getDiscountTaxCompensationAmount() :
            $block->getTotal() - $block->getDiscountAmount();
    }

    /**
     * Calculates the subtotal including tax based on the given invoice tax block.
     *
     * @param \Japan\Tax\Api\Data\InvoiceTaxBlockInterface $block The invoice tax block data.
     *
     * @return float
     */
    private function calculateSubtotalInclTax(
        \Japan\Tax\Api\Data\InvoiceTaxBlockInterface $block
    ) {
        return $block->getIsTaxIncluded() ?
            $block->getTotalInclTax() - $block->getDiscountAmount() :
            $block->getTotal() + $block->getTax() - $block->getDiscountAmount();
    }

    /**
     * Update aggregated item tax data
     *
     * @param array $aggregate
     * @param int $rate
     * @param \Japan\Tax\Model\Calculation\OrderItemAdaptor $item
     *
     * @return array
     */
    private function updateItemAggregate(array $aggregate, $rate, $item)
    {
        if (!isset($aggregate[$rate])) {
            $aggregate[$rate] = [
                'appliedRates' => [
                    [
                        'rates' => [],
                        'percent' => $rate,
                        'id' => 'Japan_Tax::$rate',
                    ],
                ],
                'taxRate' => $rate,
                'storeRate' => 0,
                'items' => [],
            ];
        }
        $aggregate[$rate]['items'][] = $item;

        return $aggregate;
    }
}
