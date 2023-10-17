<?php

namespace Magentoj\JapaneseConsumptionTax\Plugin;

class Order extends AddJctToSalesOrder
{
    /**
     * @param \Magento\Sales\Model\Order $subject
     * @param \Magento\Sales\Model\Order $result
     * @return \Magento\Sales\Model\Order
     */
    public function afterLoad(
        \Magento\Sales\Model\Order $subject,
        \Magento\Sales\Model\Order $result
    ) {
        return $this->addJctToOrder($result);
    }
}
