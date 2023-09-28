<?php

namespace Magentoj\JapaneseConsumptionTax\Block\Order;

class Jct extends \Magento\Framework\View\Element\Template
{
    /**
     * Associated array of totals
     * array(
     *  $totalCode => $totalObject
     * )
     *
     * @var array
     */
    protected $_totals;

    /**
     * @var Order
     */
    protected $_order;

    /**
     * @var \Magento\Framework\DataObject
     */
    protected $_source;

    /**
     * @var \Magento\Tax\Model\Config
     */
    protected $_taxConfig;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Tax\Model\Config $taxConfig,
        array $data = [],
    ) {
        $this->_taxConfig = $taxConfig;
        parent::__construct($context, $data);
    }

    public function getOrder()
    {
        return $this->_order;
    }

    public function getSource()
    {
        return $this->_source;
    }

    public function getTotals()
    {
        return $this->_totals;
    }

    public function initTotals()
    {
        $parent = $this->getParentBlock();
        $this->_order = $parent->getOrder();
        $this->_source = $parent->getSource();

        $jctBlock = new \Magento\Framework\DataObject(
            [
                'code' => 'jct',
                'block_name' => $this->getNameInLayout(),
                'area' => 'footer',
            ]
        );
        $store = $this->getOrder()->getStore();
        $parent->addTotal(
            $jctBlock,
            $this->_taxConfig->displaySalesTaxWithGrandTotal($store) ?
            'grand_total_incl' : 'grand_total'
        );

        $this->_totals = [];

        if ($this->_source->getIsTaxIncluded()) {
            $this->_totals['subtotal_incl_jct_10'] = new \Magento\Framework\DataObject(
                [
                    'code' => 'subtotal_incl_jct_10',
                    'value' => $this->_source->getSubtotalInclJct10(),
                    'base_value' => $this->_source->getBaseSubtotalInclJct10(),
                    'label' => __('Subtotal Subject to 10% Tax (Incl. Tax)'),
                ]
            );

            $this->_totals['subtotal_incl_jct_8'] = new \Magento\Framework\DataObject(
                [
                    'code' => 'subtotal_incl_jct_8',
                    'value' => $this->_source->getSubtotalInclJct8(),
                    'base_value' => $this->_source->getBaseSubtotalInclJct8(),
                    'label' => __('Subtotal Subject to 8% Tax (Incl. Tax)'),
                ]
            );
        } else {
            $this->_totals['subtotal_excl_jct_10'] = new \Magento\Framework\DataObject(
                [
                    'code' => 'subtotal_excl_jct_10',
                    'value' => $this->_source->getSubtotalExclJct10(),
                    'base_value' => $this->_source->getBaseSubtotalExclJct10(),
                    'label' => __('Subtotal Subject to 10% Tax'),
                ]
            );

            $this->_totals['subtotal_excl_jct_8'] = new \Magento\Framework\DataObject(
                [
                    'code' => 'subtotal_excl_jct_8',
                    'value' => $this->_source->getSubtotalExclJct8(),
                    'base_value' => $this->_source->getBaseSubtotalExclJct8(),
                    'label' => __('Subtotal Subject to 8% Tax'),
                ]
            );
        }

        $this->_totals['jct_10_amount'] = new \Magento\Framework\DataObject(
            [
                'code' => 'jct_10_amount',
                'value' => $this->_source->getJct10Amount(),
                'base_value' => $this->_source->getBaseJct10Amount(),
                'label' => __('10% Tax'),
                'is_tax' => true,
                'is_included' => $this->_source->getIsTaxIncluded(),
            ]
        );

        $this->_totals['jct_8_amount'] = new \Magento\Framework\DataObject(
            [
                'code' => 'jct_8_amount',
                'value' => $this->_source->getJct8Amount(),
                'base_value' => $this->_source->getBaseJct8Amount(),
                'label' => __('8% Tax'),
                'is_tax' => true,
                'is_included' => $this->_source->getIsTaxIncluded(),
            ]
        );

        return $this;
    }

    public function getLabelProperties()
    {
        return $this->getParentBlock()->getLabelProperties();
    }

    public function getValueProperties()
    {
        return $this->getParentBlock()->getValueProperties();
    }

    public function formatValue($total)
    {
        if (!$total->getIsFormated()) {
            return $this->getOrder()->formatPrice($total->getValue());
        }
        return $total->getValue();
    }
}
