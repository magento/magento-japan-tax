<?php
namespace Magentoj\JapaneseConsumptionTax\Model\ResourceModel\SalesOrder;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magentoj\JapaneseConsumptionTax\Model\SalesOrder;

class Collection extends AbstractCollection
{
    protected function _construct(): void
    {
        $this->_init(SalesOrder::class, \Magentoj\JapaneseConsumptionTax\Model\ResourceModel\SalesOrder::class);
    }
}
