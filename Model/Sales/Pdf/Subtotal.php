<?php
namespace Japan\Tax\Model\Sales\Pdf;

class Subtotal extends \Magento\Sales\Model\Order\Pdf\Total\DefaultTotal
{
    public function getTotalsForDisplay()
    {
        $fontSize = $this->getFontSize() ? $this->getFontSize() : 7;
        
        // TODO: Switch tax excl. and incl.
        $subtotal = $this->getOrder()->getSubtotalExclJct();
        $subtotalReduced = $this->getOrder()->getSubtotalExclReducedJct();

        $subtotalInfo = [
            [
                'amount' => $this->getAmountPrefix() . $this->getOrder()->formatPriceTxt($subtotal),
                'label' => __('10% 対象計'),
                'font_size' => $fontSize,
            ],
            [
                'amount' => $this->getAmountPrefix() . $this->getOrder()->formatPriceTxt($subtotalReduced),
                'label' => __('8% 対象計'),
                'font_size' => $fontSize,
            ]
        ];

        return $subtotalInfo;
    }
}
