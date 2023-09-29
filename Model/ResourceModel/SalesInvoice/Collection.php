<?php

namespace Magentoj\JapaneseConsumptionTax\Model\ResourceModel\SalesInvoice;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magentoj\JapaneseConsumptionTax\Model\SalesInvoice;

class Collection extends AbstractCollection
{
    protected function _construct(): void
    {
        $this->_init(SalesInvoice::class, \Magentoj\JapaneseConsumptionTax\Model\ResourceModel\SalesInvoice::class);
    }
}
