<?php

namespace Magentoj\JapaneseConsumptionTax\Model;

use Magento\Framework\Model\AbstractModel;

class SalesCreditmemo extends AbstractModel
{
    protected function _construct(): void
    {
        $this->_init(ResourceModel\SalesCreditmemo::class);
    }
}
