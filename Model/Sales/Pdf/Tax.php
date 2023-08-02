<?php
namespace Japan\Tax\Model\Sales\Pdf;

class Tax extends \Magento\Sales\Model\Order\Pdf\Total\DefaultTotal
{
    public function getTotalsForDisplay()
    {
        $fontSize = $this->getFontSize() ? $this->getFontSize() : 7;
        
        $taxAmount = $this->getOrder()->getJctAmount();
        $reducedTaxAmount = $this->getOrder()->getReducedJctAmount();

        $taxInfo = [
            [
                'amount' => $this->getAmountPrefix() . $this->getOrder()->formatPriceTxt($taxAmount),
                'label' => __('10% 税'),
                'font_size' => $fontSize,
            ],
            [
                'amount' => $this->getAmountPrefix() . $this->getOrder()->formatPriceTxt($reducedTaxAmount),
                'label' => __('8% 税'),
                'font_size' => $fontSize,
            ]
        ];

        return $taxInfo;
    }
}
