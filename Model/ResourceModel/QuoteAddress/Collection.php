<?php

namespace Magentoj\JapaneseConsumptionTax\Model\ResourceModel\QuoteAddress;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magentoj\JapaneseConsumptionTax\Model\QuoteAddress;

class Collection extends AbstractCollection
{
    protected function _construct(): void
    {
        $this->_init(QuoteAddress::class, \Magentoj\JapaneseConsumptionTax\Model\ResourceModel\QuoteAddress::class);
    }
}
