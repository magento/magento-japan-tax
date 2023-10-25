<?php
/**
 * This file is part of Japanese Consumption Tax Extension For Magento2 the project.
 *
 * Copyright (c) 2023 Adobe (or other copyright holders)
 *
 * For the full copyright and license information, please view the OSL-3.0
 * license that is bundled with this source code in the file LICENSE, or
 * at https://opensource.org/licenses/OSL-3.0
 */
namespace Magentoj\JapaneseConsumptionTax\Api;

/**
 * Tax calculation interface for Japan Consumption Tax system.
 */
interface TaxCalculationInterface
{
    /**
     * Calculate Tax
     *
     * @param \Magento\Tax\Api\Data\QuoteDetailsInterface $quoteDetails
     * @param bool $useBaseCurrency
     * @param null|int $storeId
     * @param bool $round
     * @return \Magentoj\JapaneseConsumptionTax\Api\Data\InvoiceTaxInterface
     */
    public function calculateTax(
        \Magento\Tax\Api\Data\QuoteDetailsInterface $quoteDetails,
        $useBaseCurrency,
        $storeId = null,
        $round = true
    );
}
