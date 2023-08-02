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
        if ($this->getOrder()->getSubtotalExclJct()) {
          $taxDataObject = new \Magento\Framework\DataObject(
              [
                  'code' => 'subtotal_excl_jct',
                  'value' => $this->getOrder()->getSubtotalExclJct(),
                  'label' => __('10% 対象計'),
              ]
          );
          $this->getParentBlock()->addTotalBefore($taxDataObject, 'subtotal');
        }

        if ($this->getOrder()->getSubtotalExclReducedJct()) {
          $taxDataObject = new \Magento\Framework\DataObject(
              [
                  'code' => 'subtotal_excl_reduced_jct',
                  'value' => $this->getOrder()->getSubtotalExclReducedJct(),
                  'label' => __('8% 対象計'),
              ]
          );
          $this->getParentBlock()->addTotalBefore($taxDataObject, 'subtotal');
        }

        if ($this->getOrder()->getJctAmount()) {
          $taxDataObject = new \Magento\Framework\DataObject(
              [
                  'code' => 'jct_amount',
                  'value' => $this->getOrder()->getJctAmount(),
                  'label' => __('10% 税'),
              ]
          );
          $this->getParentBlock()->addTotalBefore($taxDataObject, 'tax');
        }

        if ($this->getOrder()->getReducedJctAmount()) {
          $taxDataObject = new \Magento\Framework\DataObject(
              [
                  'code' => 'reduced_jct_amount',
                  'value' => $this->getOrder()->getReducedJctAmount(),
                  'label' => __('8% 税'),
              ]
          );
          $this->getParentBlock()->addTotalBefore($taxDataObject, 'tax');
        }

        $this->getParentBlock()->removeTotal('subtotal');
        $this->getParentBlock()->removeTotal('tax');
        
        return $this;
    }
}
