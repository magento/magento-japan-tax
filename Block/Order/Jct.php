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

        $extensionAttributes = $this->_source->getExtensionAttributes();
        $jctTotals = $extensionAttributes->getJctTotals();

        $this->_totals = [];

        if ($jctTotals === null) {
            return $this;
        }

        if ($jctTotals->getIsTaxIncluded()) {
            $this->_totals[$jctTotals::KEY_SUBTOTAL_INCL_JCT_10] = new \Magento\Framework\DataObject(
                [
                    'code' => $jctTotals::KEY_SUBTOTAL_INCL_JCT_10,
                    'value' => $jctTotals->getSubtotalInclJct10(),
                    'base_value' => $jctTotals->getBaseSubtotalInclJct10(),
                    'label' => __('Subtotal Subject to 10% Tax (Incl. Tax)'),
                ]
            );

            $this->_totals[$jctTotals::KEY_SUBTOTAL_INCL_JCT_8] = new \Magento\Framework\DataObject(
                [
                    'code' => $jctTotals::KEY_SUBTOTAL_INCL_JCT_10,
                    'value' => $jctTotals->getSubtotalInclJct8(),
                    'base_value' => $jctTotals->getBaseSubtotalInclJct8(),
                    'label' => __('Subtotal Subject to 8% Tax (Incl. Tax)'),
                ]
            );
        } else {
            $this->_totals[$jctTotals::KEY_SUBTOTAL_EXCL_JCT_10] = new \Magento\Framework\DataObject(
                [
                    'code' => $jctTotals::KEY_SUBTOTAL_EXCL_JCT_10,
                    'value' => $jctTotals->getSubtotalExclJct10(),
                    'base_value' => $jctTotals->getBaseSubtotalExclJct10(),
                    'label' => __('Subtotal Subject to 10% Tax'),
                ]
            );

            $this->_totals[$jctTotals::KEY_SUBTOTAL_EXCL_JCT_8] = new \Magento\Framework\DataObject(
                [
                    'code' => $jctTotals::KEY_SUBTOTAL_EXCL_JCT_8,
                    'value' => $jctTotals->getSubtotalExclJct8(),
                    'base_value' => $jctTotals->getBaseSubtotalExclJct8(),
                    'label' => __('Subtotal Subject to 8% Tax'),
                ]
            );
        }

        $this->_totals[$jctTotals::KEY_JCT_10_AMOUNT] = new \Magento\Framework\DataObject(
            [
                'code' => $jctTotals::KEY_JCT_10_AMOUNT,
                'value' => $jctTotals->getJct10Amount(),
                'base_value' => $jctTotals->getBaseJct10Amount(),
                'label' => __('10% Tax'),
                'is_tax' => true,
                'is_included' => $jctTotals->getIsTaxIncluded(),
            ]
        );

        $this->_totals[$jctTotals::KEY_JCT_8_AMOUNT] = new \Magento\Framework\DataObject(
            [
                'code' => $jctTotals::KEY_JCT_8_AMOUNT,
                'value' => $jctTotals->getJct8Amount(),
                'base_value' => $jctTotals->getBaseJct8Amount(),
                'label' => __('8% Tax'),
                'is_tax' => true,
                'is_included' => $jctTotals->getIsTaxIncluded(),
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
