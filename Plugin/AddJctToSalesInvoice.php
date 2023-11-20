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
use Magentoj\JapaneseConsumptionTax\Api\Data\JctTotalsInterfaceFactory;
use Magentoj\JapaneseConsumptionTax\Model\SalesInvoice;
use Magentoj\JapaneseConsumptionTax\Model\SalesInvoiceFactory as ModelFactory;
use Magentoj\JapaneseConsumptionTax\Model\ResourceModel\SalesInvoiceFactory as ResourceModelFactory;
use Magentoj\JapaneseConsumptionTax\Model\ResourceModel\SalesInvoice\CollectionFactory;

class AddJctToSalesInvoice
{
    protected ModelFactory $magentojSalesInvoiceModelFactory;

    protected ResourceModelFactory $magentojSalesInvoiceResourceModelFactory;

    protected CollectionFactory $magentojSalesInvoiceCollectionFactory;

    protected JctTotalsInterfaceFactory $jctTotalsInterfaceFactory;

    /**
     * AddJctToSalesInvoice constructor.
     * @param ModelFactory $magentojSalesInvoiceModelFactory
     * @param ResourceModelFactory $magentojSalesInvoiceResourceModelFactory
     * @param CollectionFactory $magentojSalesInvoiceCollectionFactory
     * @param JctTotalsInterfaceFactory $jctTotalsInterfaceFactory
     */
    public function __construct(
        ModelFactory $magentojSalesInvoiceModelFactory,
        ResourceModelFactory $magentojSalesInvoiceResourceModelFactory,
        CollectionFactory $magentojSalesInvoiceCollectionFactory,
        JctTotalsInterfaceFactory $jctTotalsInterfaceFactory
    ) {
        $this->magentojSalesInvoiceModelFactory = $magentojSalesInvoiceModelFactory;
        $this->magentojSalesInvoiceResourceModelFactory = $magentojSalesInvoiceResourceModelFactory;
        $this->magentojSalesInvoiceCollectionFactory = $magentojSalesInvoiceCollectionFactory;
        $this->jctTotalsInterfaceFactory = $jctTotalsInterfaceFactory;
    }

    /**
     * @param InvoiceInterface $invoice
     * @return InvoiceInterface
     */
    protected function addJctToInvoice(InvoiceInterface $invoice)
    {
        $existingInvoice = $this->getSalesInvoiceByInvoiceId($invoice->getEntityId());

        if (!$existingInvoice->getJctTotals()) {
            return $invoice;
        }

        $invoiceExtension = $invoice->getExtensionAttributes();
        $jctTotals = $this->jctTotalsInterfaceFactory->create(
            [
                'data' => json_decode($existingInvoice->getJctTotals(), true)
            ]
        );
        $invoiceExtension->setJctTotals($jctTotals);

        $invoice->setExtensionAttributes($invoiceExtension);

        return $invoice;
    }

    /**
     * @param int $invoiceId
     * @return SalesInvoice
     */
    protected function getSalesInvoiceByInvoiceId(int $invoiceId)
    {
        $collection = $this->magentojSalesInvoiceCollectionFactory->create();

        return $collection
            ->addFieldToFilter('invoice_id', $invoiceId)
            ->getFirstItem();
    }
}
