<?php

namespace Magentoj\JapaneseConsumptionTax\Plugin;

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
