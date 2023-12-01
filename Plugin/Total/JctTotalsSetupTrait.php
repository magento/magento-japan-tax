<?php
/**
 * This file is part of the Japanese Consumption Tax Extension For Magento2 project.
 *
 * Copyright (c) 2023 Adobe (or other copyright holders)
 *
 * For the full copyright and license information, please view the OSL-3.0
 * license that is bundled with this source code in the file LICENSE, or
 * at https://opensource.org/licenses/OSL-3.0
 */
namespace Magentoj\JapaneseConsumptionTax\Plugin\Total;

use Magentoj\JapaneseConsumptionTax\Api\Data\InvoiceTaxBlockInterface;
use Magentoj\JapaneseConsumptionTax\Constants;
use Magentoj\JapaneseConsumptionTax\Model\Calculation\JctTaxCalculator;

trait JctTotalsSetupTrait
{
    /**
     * Returns the tax blocks from the aggregated item tax data
     *
     * @param JctTaxCalculator $calculator
     * @param array $aggregate
     * @param bool $isTaxIncluded
     * @param string $currencyCode
     *
     * @return InvoiceTaxBlockInterface[]
     */
    private function getJctBlocks(
        JctTaxCalculator $calculator,
        array $aggregate,
        bool $isTaxIncluded,
        string $currencyCode,
    ) {
        $blocks = [];
        if ($isTaxIncluded) {
            foreach ($aggregate as $data) {
                $blocks[] = $calculator->calculateWithTaxInPrice(
                    $data["items"],
                    $data["taxRate"],
                    $data["appliedRates"],
                    $currencyCode
                );
            }
        } else {
            foreach ($aggregate as $data) {
                $blocks[] = $calculator->calculateWithTaxNotInPrice(
                    $data["items"],
                    $data["taxRate"],
                    $data["appliedRates"],
                    $currencyCode
                );
            }
        }
        return $blocks;
    }

    /**
     * Returns the JCT totals array from the given tax blocks
     *
     * @param InvoiceTaxBlockInterface[] $baseBlocks
     * @param InvoiceTaxBlockInterface[] $blocks
     *
     * @return array
     */
    private function getJctTotalsArray(array $baseBlocks, array $blocks) {
        $jctTotals = [];

        foreach ($baseBlocks as $block) {
            if (in_array($block->getTaxPercent(), Constants::JCT_PERCENTS)) {
                $jctTotals = $this->updateJctTotalsArray($jctTotals, $block, 'base_');
            }
        }

        foreach ($blocks as $block) {
            if (in_array($block->getTaxPercent(), Constants::JCT_PERCENTS)) {
                $jctTotals = $this->updateJctTotalsArray($jctTotals, $block);
            }
        }

        return $jctTotals;
    }
    
    /**
     * Updates the JCT totals array for the given tax block
     * 
     * @param array $totals
     * @param InvoiceTaxBlockInterface $block The invoice tax block data.
     * @param string $prefix
     *
     * @return array
     */
    private function updateJctTotalsArray(
        $totals,
        InvoiceTaxBlockInterface $block,
        $prefix = '',
    ) {
        $taxPercent = $block->getTaxPercent();

        $totals["{$prefix}subtotal_excl_jct_{$taxPercent}"] = $this->calculateSubtotalExclTax($block);
        $totals["{$prefix}subtotal_incl_jct_{$taxPercent}"] = $this->calculateSubtotalInclTax($block);
        $totals["{$prefix}jct_{$taxPercent}_amount"] = $block->getTax();
        $totals["is_tax_included"] = $block->getIsTaxIncluded();

        return $totals;
    }

    /**
     * Calculates the subtotal excluding tax based on the given invoice tax block.
     *
     * @param InvoiceTaxBlockInterface $block The invoice tax block data.
     *
     * @return float
     */
    private function calculateSubtotalExclTax(InvoiceTaxBlockInterface $block)
    {
        return $block->getIsTaxIncluded() ?
            $block->getTotal() - $block->getDiscountAmount() + $block->getDiscountTaxCompensationAmount() :
            $block->getTotal() - $block->getDiscountAmount();
    }

    /**
     * Calculates the subtotal including tax based on the given invoice tax block.
     *
     * @param InvoiceTaxBlockInterface $block The invoice tax block data.
     *
     * @return float
     */
    private function calculateSubtotalInclTax(InvoiceTaxBlockInterface $block)
    {
        return $block->getIsTaxIncluded() ?
            $block->getTotalInclTax() - $block->getDiscountAmount() :
            $block->getTotal() + $block->getTax() - $block->getDiscountAmount();
    }

    /**
     * Update aggregated item tax data
     *
     * @param array $aggregate
     * @param int $rate
     * @param \Magentoj\JapaneseConsumptionTax\Model\Calculation\OrderItemAdaptor $item
     *
     * @return array
     */
    private function updateItemAggregate(array $aggregate, $rate, $item)
    {
        if (!isset($aggregate[$rate])) {
            $aggregate[$rate] = [
                "appliedRates" => [
                    [
                        "rates" => [],
                        "percent" => $rate,
                        "id" => "Magentoj_JapaneseConsumptionTax::$rate",
                    ],
                ],
                "taxRate" => $rate,
                "storeRate" => 0,
                "items" => [],
            ];
        }
        $aggregate[$rate]['items'][] = $item;

        return $aggregate;
    }
}
