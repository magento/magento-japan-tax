<?php

namespace Magentoj\JapaneseConsumptionTax\Model;

use Magento\Framework\Model\AbstractModel;

class SalesInvoice extends AbstractModel
{
    protected function _construct(): void
    {
        $this->_init(ResourceModel\SalesInvoice::class);
    }
}
