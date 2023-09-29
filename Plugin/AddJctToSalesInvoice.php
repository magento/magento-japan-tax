<?php

namespace Magentoj\JapaneseConsumptionTax\Plugin;

use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\InvoiceSearchResultInterface;
use Magento\Sales\Model\Order\Invoice;
use Magentoj\JapaneseConsumptionTax\Api\Data\JctTotalsInterfaceFactory;
use Magentoj\JapaneseConsumptionTax\Model\SalesInvoice;
use Magentoj\JapaneseConsumptionTax\Model\SalesInvoiceFactory as ModelFactory;
use Magentoj\JapaneseConsumptionTax\Model\ResourceModel\SalesInvoiceFactory as ResourceModelFactory;
use Magentoj\JapaneseConsumptionTax\Model\ResourceModel\SalesInvoice\CollectionFactory;

class AddJctToSalesInvoice
{
    private ModelFactory $magentojSalesInvoiceModelFactory;

    private ResourceModelFactory $magentojSalesInvoiceResourceModelFactory;

    private CollectionFactory $magentojSalesInvoiceCollectionFactory;

    private JctTotalsInterfaceFactory $jctTotalsInterfaceFactory;

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
     * @param Invoice $subject
     * @param Invoice $result
     * @return Invoice
     */
    public function afterSave(
        Invoice $subject,
        Invoice $result
    ) {
        $model = $this->getSalesInvoiceByInvoiceId($result->getEntityId());

        if ($model->getData()) {
            return $result;
        }

        $jctTotals = $result->getExtensionAttributes()->getJctTotals();
        $model->setJctTotals(json_encode($jctTotals->getData()));
        $model->setInvoiceId($result->getEntityId());

        $resourceModel = $this->magentojSalesInvoiceResourceModelFactory->create();
        $resourceModel->save($model);

        return $result;
    }

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

    /**
     * @param int $invoiceId
     * @return SalesInvoice
     */
    private function getSalesInvoiceByInvoiceId(int $invoiceId)
    {
        $collection = $this->magentojSalesInvoiceCollectionFactory->create();

        return $collection
            ->addFieldToFilter('invoice_id', $invoiceId)
            ->getFirstItem();
    }
}
