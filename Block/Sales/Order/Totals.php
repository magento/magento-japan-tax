<?php
namespace Japan\Tax\Block\Sales\Order;

class Totals extends \Magento\Framework\View\Element\Template
{
    public function getOrder()
    {
        return $this->getParentBlock()->getOrder();
    }

    public function initTotals()
    {
        // TODO: Switch tax excl. and incl.
        $taxDataObject = new \Magento\Framework\DataObject(
            [
                'code' => 'subtotal_excl_jct_10',
                'value' => $this->getOrder()->getSubtotalExclJct10(),
                'label' => __('Subtotal Subject to 10% Tax'),
            ]
        );
        $this->getParentBlock()->addTotalBefore($taxDataObject, 'subtotal');

        $taxDataObject = new \Magento\Framework\DataObject(
            [
                'code' => 'subtotal_excl_jct_8',
                'value' => $this->getOrder()->getSubtotalExclJct8(),
                'label' => __('Subtotal Subject to 8% Tax'),
            ]
        );
        $this->getParentBlock()->addTotalBefore($taxDataObject, 'subtotal');

        $taxDataObject = new \Magento\Framework\DataObject(
            [
                'code' => 'jct_10_amount',
                'value' => $this->getOrder()->getJct10Amount(),
                'label' => __('10% Tax'),
            ]
        );
        $this->getParentBlock()->addTotalBefore($taxDataObject, 'tax');

        $taxDataObject = new \Magento\Framework\DataObject(
            [
                'code' => 'jct_8_amount',
                'value' => $this->getOrder()->getJct8Amount(),
                'label' => __('8% Tax'),
            ]
        );
        $this->getParentBlock()->addTotalBefore($taxDataObject, 'tax');

        $this->getParentBlock()->removeTotal('subtotal');
        $this->getParentBlock()->removeTotal('tax');

        return $this;
    }
}
