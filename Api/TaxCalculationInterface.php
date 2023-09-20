<?php

namespace Japan\Tax\Api;

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
     * @return \Japan\Tax\Api\Data\InvoiceTaxInterface
     */
    public function calculateTax(
        \Magento\Tax\Api\Data\QuoteDetailsInterface $quoteDetails,
        $useBaseCurrency,
        $storeId = null,
        $round = true
    );
}
