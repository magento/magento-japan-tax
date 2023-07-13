<?php

namespace Japan\Tax\Api;

/**
 * Tax calculation interface.
 * @api
 * @since 100.0.2
 */
interface CurrencyRoundingInterface
{
    /**
     * Round currency to significant precision.
     *
     * Rounding method may be configured at admin page at
     *
     * @param string $currencyCode
     * @param float $amount
     * @return float
     */
    public function round(string $currencyCode, float $amount);
}