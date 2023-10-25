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
        $existingInvoice = $this->getSalesInvoiceByInvoiceId($result->getEntityId());

        if (!$existingInvoice->getJctTotals()) {
            return $result;
        }

        $orderExtension = $result->getExtensionAttributes();
        $jctTotals = $this->jctTotalsInterfaceFactory->create(
            [
                'data' => json_decode($existingInvoice->getJctTotals(), true)
            ]
        );
        $orderExtension->setJctTotals($jctTotals);

        $result->setExtensionAttributes($orderExtension);

        return $result;
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
