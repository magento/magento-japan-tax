<?php
namespace Japan\Tax\Model\Sales\Pdf;

class Tax extends \Magento\Sales\Model\Order\Pdf\Total\DefaultTotal
{
    public function getTotalsForDisplay()
    {
        $fontSize = $this->getFontSize() ? $this->getFontSize() : 7;

        $jct10Amount = $this->getOrder()->getJct10Amount();
        $jct8Amount = $this->getOrder()->getJct8Amount();

        $taxInfo = [
            [
                'amount' => $this->getAmountPrefix() . $this->getOrder()->formatPriceTxt($jct10Amount),
                'label' => __('10% Tax'),
                'font_size' => $fontSize,
            ],
            [
                'amount' => $this->getAmountPrefix() . $this->getOrder()->formatPriceTxt($jct8Amount),
                'label' => __('8% Tax'),
                'font_size' => $fontSize,
            ]
        ];

        return $taxInfo;
    }
}
