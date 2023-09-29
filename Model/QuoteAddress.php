<?php

namespace Magentoj\JapaneseConsumptionTax\Model;

use Magento\Framework\Model\AbstractModel;

class QuoteAddress extends AbstractModel
{
    protected function _construct(): void
    {
        $this->_init(ResourceModel\QuoteAddress::class);
    }
}
