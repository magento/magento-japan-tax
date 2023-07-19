<?php

namespace Japan\Tax\Api;

/**
 * Tax calculation interface.
 * @api
 * @since 100.0.2
 */
interface TaxCalculationInterface
{
    /**#@+
     * Type of calculation used
     */
    const CALC_UNIT_BASE = 'UNIT_BASE_CALCULATION';
    const CALC_ROW_BASE = 'ROW_BASE_CALCULATION';
    const CALC_TOTAL_BASE = 'TOTAL_BASE_CALCULATION';
    /**#@-*/

    /**
     * Calculate Tax
     *
     * @param \Magento\Tax\Api\Data\QuoteDetailsInterface $quoteDetails
     * @param null|string $baseCurrency
     * @param null|int $storeId
     * @param bool $round
     * @return \Japan\Tax\Api\Data\InvoiceTaxInterface
     */
    public function calculateTax(
        \Magento\Tax\Api\Data\QuoteDetailsInterface $quoteDetails,
        $baseCurrency = null,
        $storeId = null,
        $round = true
    );
}