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
namespace Magentoj\JapaneseConsumptionTax\Model\Totals;

class Jct extends \Magento\Quote\Model\Quote\Address\Total\AbstractTotal
{
    public function __construct()
    {
        $this->setCode('jct');
    }

    public function fetch(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        $result = [];
        $jctTotals = $total->getJctTotals();

        if ($jctTotals === null) {
            return $result;
        }

        $subtotals = $jctTotals->getIsTaxIncluded() ?
        [
            [
                'code' => $jctTotals::KEY_SUBTOTAL_INCL_JCT_10,
                'title' => __('Subtotal Subject to 10% Tax (Incl. Tax)'),
                'value' => $jctTotals->getSubtotalInclJct10(),
            ],
            [
                'code' => $jctTotals::KEY_SUBTOTAL_INCL_JCT_8,
                'title' => __('Subtotal Subject to 8% Tax (Incl. Tax)'),
                'value' => $jctTotals->getSubtotalInclJct8(),
            ]
        ] :
        [
            [
                'code' => $jctTotals::KEY_SUBTOTAL_EXCL_JCT_10,
                'title' => __('Subtotal Subject to 10% Tax (Excl. Tax)'),
                'value' => $jctTotals->getSubtotalExclJct10(),
            ],
            [
                'code' => $jctTotals::KEY_SUBTOTAL_EXCL_JCT_8,
                'title' => __('Subtotal Subject to 8% Tax (Excl. Tax)'),
                'value' => $jctTotals->getSubtotalExclJct8(),
            ]
        ];
        array_push($result, ...$subtotals);

        array_push(
            $result,
            [
                'code' => $jctTotals::KEY_JCT_10_AMOUNT,
                'title' => __('10% Tax'),
                'value' => $jctTotals->getJct10Amount(),
            ],
            [
                'code' => $jctTotals::KEY_JCT_8_AMOUNT,
                'title' => __('8% Tax'),
                'value' => $jctTotals->getJct8Amount(),
            ]
        );

        return $result;
    }
}
