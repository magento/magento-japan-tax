<?php
namespace Japan\Tax\Model\Sales\Pdf;

class Subtotal extends \Magento\Sales\Model\Order\Pdf\Total\DefaultTotal
{
    public function getTotalsForDisplay()
    {
        $fontSize = $this->getFontSize() ? $this->getFontSize() : 7;
        
        // TODO: Switch tax excl. and incl.
        $subtotalJct10 = $this->getOrder()->getSubtotalExclJct10();
        $subtotalJct8 = $this->getOrder()->getSubtotalExclJct8();

        $subtotalInfo = [
            [
                'amount' => $this->getAmountPrefix() . $this->getOrder()->formatPriceTxt($subtotalJct10),
                'label' => __('Subtotal Subject to 10% Tax'),
                'font_size' => $fontSize,
            ],
            [
                'amount' => $this->getAmountPrefix() . $this->getOrder()->formatPriceTxt($subtotalJct8),
                'label' => __('Subtotal Subject to 8% Tax'),
                'font_size' => $fontSize,
            ]
        ];

        return $subtotalInfo;
    }
}
