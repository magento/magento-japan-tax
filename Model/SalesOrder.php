<?php

namespace Magentoj\JapaneseConsumptionTax\Model;

use Magento\Framework\Model\AbstractModel;

class SalesOrder extends AbstractModel
{
    protected function _construct(): void
    {
        $this->_init(ResourceModel\SalesOrder::class);
    }
}
