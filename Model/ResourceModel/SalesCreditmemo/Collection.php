<?php

namespace Magentoj\JapaneseConsumptionTax\Model\ResourceModel\SalesCreditmemo;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magentoj\JapaneseConsumptionTax\Model\SalesCreditmemo;

class Collection extends AbstractCollection
{
    protected function _construct(): void
    {
        $this->_init(
            SalesCreditmemo::class,
            \Magentoj\JapaneseConsumptionTax\Model\ResourceModel\SalesCreditmemo::class
        );
    }
}
