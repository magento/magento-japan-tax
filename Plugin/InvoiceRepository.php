<?php
/**
 * This file is part of the Japanese Consumption Tax Extension For Magento2 project.
 *
 * Copyright (c) 2023 Adobe (or other copyright holders)
 *
 * For the full copyright and license information, please view the OSL-3.0
 * license that is bundled with this source code in the file LICENSE, or
 * at https://opensource.org/licenses/OSL-3.0
 */
namespace Magentoj\JapaneseConsumptionTax\Plugin;

use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\InvoiceSearchResultInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;

class InvoiceRepository extends AddJctToSalesInvoice
{
    /**
     * @param InvoiceRepositoryInterface $subject
     * @param InvoiceInterface $result
     * @return InvoiceInterface
     */
    public function afterGet(
        InvoiceRepositoryInterface $subject,
        InvoiceInterface $result
    ) {
        return $this->addJctToInvoice($result);
    }

    /**
     * @param InvoiceRepositoryInterface $subject
     * @param InvoiceSearchResultInterface $result
     * @return mixed
     */
    public function afterGetList(
        InvoiceRepositoryInterface $subject,
        InvoiceSearchResultInterface $result
    ) {
        foreach ($result->getItems() as $invoice) {
            $this->afterGet($subject, $invoice);
        }

        return $result;
    }
}
