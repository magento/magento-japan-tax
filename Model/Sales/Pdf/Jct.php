<?php
namespace Japan\Tax\Model\Sales\Pdf;

use Magento\Tax\Helper\Data;
use Magento\Tax\Model\Calculation;
use Magento\Tax\Model\ResourceModel\Sales\Order\Tax\CollectionFactory;

class Jct extends \Magento\Sales\Model\Order\Pdf\Total\DefaultTotal
{
    /**
     * @var Data
     */
    protected $_taxHelper;

    public function __construct(
        Data $taxHelper,
        Calculation $taxCalculation,
        CollectionFactory $ordersFactory,
        array $data = []
    ) {
        $this->_taxHelper = $taxHelper;
        parent::__construct($taxHelper, $taxCalculation, $ordersFactory, $data);
    }

    public function getTotalsForDisplay()
    {
        $fontSize = $this->getFontSize() ? $this->getFontSize() : 7;

        $taxInclude = $this->_taxHelper->priceIncludesTax();

        $jctInfo = [
            [
                'amount' => $this->getAmountPrefix() . $this->getOrder()->formatPriceTxt(
                    $taxInclude ? 
                        $this->getOrder()->getSubtotalInclJct10() : $this->getOrder()->getSubtotalExclJct10()
                ),
                'label' => $taxInclude ?
                    __('Subtotal Subject to 10% Tax (Incl. Tax)') : __('Subtotal Subject to 10% Tax'),
                'font_size' => $fontSize,
            ],
            [
                'amount' => $this->getAmountPrefix() . $this->getOrder()->formatPriceTxt(
                    $taxInclude ?
                        $this->getOrder()->getSubtotalInclJct8() : $this->getOrder()->getSubtotalExclJct8()
                ),
                'label' => $taxInclude ?
                    __('Subtotal Subject to 8% Tax (Incl. Tax)') : __('Subtotal Subject to 8% Tax'),
                'font_size' => $fontSize,
            ],
            [
                'amount' => $this->getFormatJctTxt($this->getOrder()->getJct10Amount()),
                'label' => __('10% Tax'),
                'font_size' => $fontSize,
            ],
            [
                'amount' => $this->getFormatJctTxt($this->getOrder()->getJct8Amount()),
                'label' => __('8% Tax'),
                'font_size' => $fontSize,
            ]
        ];

        return $jctInfo;
    }

    private function getFormatJctTxt(float $amount)
    {
        $txt = $this->getAmountPrefix() . $this->getOrder()->formatPriceTxt($amount);
        
        if ($this->_taxHelper->priceIncludesTax()) {
            return '(' . $txt . ')';
        }
        return $txt;
    }
}
