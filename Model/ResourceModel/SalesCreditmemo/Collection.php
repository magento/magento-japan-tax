<?php
/**
 * This file is part of Japanese Consumption Tax Extension For Magento2 the project.
 *
 * Copyright (c) 2023 Adobe (or other copyright holders)
 *
 * For the full copyright and license information, please view the OSL-3.0
 * license that is bundled with this source code in the file LICENSE, or
 * at https://opensource.org/licenses/OSL-3.0
 */
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
