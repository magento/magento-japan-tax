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
namespace Magentoj\JapaneseConsumptionTax\Model\Order\Pdf\Total;

use Magento\Tax\Helper\Data;
use Magento\Tax\Model\Calculation;
use Magento\Tax\Model\ResourceModel\Sales\Order\Tax\CollectionFactory;

class Jct extends \Magento\Sales\Model\Order\Pdf\Total\DefaultTotal
{
    public function getTotalsForDisplay()
    {
        $fontSize = $this->getFontSize() ? $this->getFontSize() : 7;

        $extensionAttributes = $this->getSource()->getExtensionAttributes();
        $jctTotals = $extensionAttributes->getJctTotals();

        if ($jctTotals === null) {
            return [];
        }

        $isTaxIncluded = $jctTotals->getIsTaxIncluded();

        $jctInfo = [
            [
                'amount' => $this->getAmountPrefix() . $this->getOrder()->formatPriceTxt(
                    $isTaxIncluded ?
                        $jctTotals->getSubtotalInclJct10() : $jctTotals->getSubtotalExclJct10()
                ),
                'label' => $isTaxIncluded ?
                    __('Subtotal Subject to 10% Tax (Incl. Tax)') : __('Subtotal Subject to 10% Tax'),
                'font_size' => $fontSize,
            ],
            [
                'amount' => $this->getAmountPrefix() . $this->getOrder()->formatPriceTxt(
                    $isTaxIncluded ?
                        $jctTotals->getSubtotalInclJct8() : $jctTotals->getSubtotalExclJct8()
                ),
                'label' => $isTaxIncluded ?
                    __('Subtotal Subject to 8% Tax (Incl. Tax)') : __('Subtotal Subject to 8% Tax'),
                'font_size' => $fontSize,
            ],
            [
                'amount' => $this->getFormatJctTxt($jctTotals->getJct10Amount(), $isTaxIncluded),
                'label' => __('10% Tax'),
                'font_size' => $fontSize,
            ],
            [
                'amount' => $this->getFormatJctTxt($jctTotals->getJct8Amount(), $isTaxIncluded),
                'label' => __('8% Tax'),
                'font_size' => $fontSize,
            ]
        ];

        return $jctInfo;
    }

    private function getFormatJctTxt($amount, $isTaxIncluded)
    {
        $txt = $this->getAmountPrefix() . $this->getOrder()->formatPriceTxt($amount);
        return $isTaxIncluded ? '(' . $txt . ')' : $txt;
    }
}
