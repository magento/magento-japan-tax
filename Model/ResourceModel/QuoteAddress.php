<?php

namespace Magentoj\JapaneseConsumptionTax\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class QuoteAddress extends AbstractDb
{
    public const MAIN_TABLE = 'magentoj_jct_quote_address';

    public const ID_FIELD_NAME = 'id';

    protected function _construct(): void
    {
        $this->_init(self::MAIN_TABLE, self::ID_FIELD_NAME);
    }
}
