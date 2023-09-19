<?php
namespace Japan\Tax\Model\Order\Pdf\Total;

use Magento\Tax\Helper\Data;
use Magento\Tax\Model\Calculation;
use Magento\Tax\Model\ResourceModel\Sales\Order\Tax\CollectionFactory;

class Jct extends \Magento\Sales\Model\Order\Pdf\Total\DefaultTotal
{
    public function getTotalsForDisplay()
    {
        $fontSize = $this->getFontSize() ? $this->getFontSize() : 7;

        $taxInclude = $this->getSource()->getIsTaxIncluded();

        $jctInfo = [
            [
                'amount' => $this->getAmountPrefix() . $this->getOrder()->formatPriceTxt(
                    $taxInclude ? 
                        $this->getSource()->getSubtotalInclJct10() : $this->getSource()->getSubtotalExclJct10()
                ),
                'label' => $taxInclude ?
                    __('Subtotal Subject to 10% Tax (Incl. Tax)') : __('Subtotal Subject to 10% Tax'),
                'font_size' => $fontSize,
            ],
            [
                'amount' => $this->getAmountPrefix() . $this->getOrder()->formatPriceTxt(
                    $taxInclude ?
                        $this->getSource()->getSubtotalInclJct8() : $this->getSource()->getSubtotalExclJct8()
                ),
                'label' => $taxInclude ?
                    __('Subtotal Subject to 8% Tax (Incl. Tax)') : __('Subtotal Subject to 8% Tax'),
                'font_size' => $fontSize,
            ],
            [
                'amount' => $this->getFormatJctTxt($this->getSource()->getJct10Amount()),
                'label' => __('10% Tax'),
                'font_size' => $fontSize,
            ],
            [
                'amount' => $this->getFormatJctTxt($this->getSource()->getJct8Amount()),
                'label' => __('8% Tax'),
                'font_size' => $fontSize,
            ]
        ];

        return $jctInfo;
    }

    private function getFormatJctTxt($amount)
    {
        $txt = $this->getAmountPrefix() . $this->getOrder()->formatPriceTxt($amount);
        
        if ($this->getSource()->getIsTaxIncluded()) {
            return '(' . $txt . ')';
        }
        return $txt;
    }
}
