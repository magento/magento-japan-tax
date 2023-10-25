<?php
/**
 * This file is part of Japanese Consumption Tax Extension For Magento2 the  project.
 *
 * Copyright (c) 2023 Adobe (or other copyright holders)
 *
 * For the full copyright and license information, please view the OSL-3.0
 * license that is bundled with this source code in the file LICENSE, or
 * at https://opensource.org/licenses/OSL-3.0
 */
namespace Magentoj\JapaneseConsumptionTax\Model;

use Magentoj\JapaneseConsumptionTax\Model\CurrencyRoundingFactory;

/**
 * Currency rounding service.
 */
class CurrencyRounding
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
    public function round(string $currencyCode, float $amount): float
    {
        $roundingMode = $this->getRoundingMode();

        $formatter = $this->createCurrencyFormatter($currencyCode);
        $formatter->setAttribute(\NumberFormatter::ROUNDING_MODE, $roundingMode);

        $formatted = $formatter->format($amount);
        $rounded = $formatter->parse($formatted, \NumberFormatter::TYPE_DOUBLE);
        return $rounded;
    }

    /**
     * Create Intl Number Formatter for currency.
     *
     * @param string $currencyCode
     * @return \NumberFormatter
     */
    private function createCurrencyFormatter(string $currencyCode): \NumberFormatter
    {
        return new \NumberFormatter('@currency=' . $currencyCode, \NumberFormatter::CURRENCY);
    }

    /**
     * Get Intl rounding mode.
     *
     * Read configured rounding mode and map to Intl constant value.
     *
     * @return int
     */
    private function getRoundingMode(): int
    {
        return \NumberFormatter::ROUND_DOWN;
    }
}
