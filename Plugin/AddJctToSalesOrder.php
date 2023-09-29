<?php

namespace Magentoj\JapaneseConsumptionTax\Plugin;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magentoj\JapaneseConsumptionTax\Api\Data\JctTotalsInterfaceFactory;
use Magentoj\JapaneseConsumptionTax\Model\SalesOrder;
use Magentoj\JapaneseConsumptionTax\Model\SalesOrderFactory as ModelFactory;
use Magentoj\JapaneseConsumptionTax\Model\ResourceModel\SalesOrderFactory as ResourceModelFactory;
use Magentoj\JapaneseConsumptionTax\Model\ResourceModel\SalesOrder\CollectionFactory;

class AddJctToSalesOrder
{
    private ModelFactory $magentojSalesOrderModelFactory;

    private ResourceModelFactory $magentojSalesOrderResourceModelFactory;

    private CollectionFactory $magentojSalesOrderCollectionFactory;

    private JctTotalsInterfaceFactory $jctTotalsInterfaceFactory;

    /**
     * AddJctToSalesOrder constructor.
     * @param ModelFactory $magentojSalesOrderModelFactory
     * @param ResourceModelFactory $magentojSalesOrderResourceModelFactory
     * @param CollectionFactory $magentojSalesOrderCollectionFactory
     * @param JctTotalsInterfaceFactory $jctTotalsInterfaceFactory
     */
    public function __construct(
        ModelFactory $magentojSalesOrderModelFactory,
        ResourceModelFactory $magentojSalesOrderResourceModelFactory,
        CollectionFactory $magentojSalesOrderCollectionFactory,
        JctTotalsInterfaceFactory $jctTotalsInterfaceFactory
    ) {
        $this->magentojSalesOrderModelFactory = $magentojSalesOrderModelFactory;
        $this->magentojSalesOrderResourceModelFactory = $magentojSalesOrderResourceModelFactory;
        $this->magentojSalesOrderCollectionFactory = $magentojSalesOrderCollectionFactory;
        $this->jctTotalsInterfaceFactory = $jctTotalsInterfaceFactory;
    }

    /**
     * @param OrderRepositoryInterface $subject
     * @param OrderInterface $result
     * @return OrderInterface
     */
    public function afterSave(
        OrderRepositoryInterface $subject,
        OrderInterface $result
    ) {
        $model = $this->getSalesOrderByOrderId($result->getEntityId());

        if ($model->getData()) {
            return $result;
        }

        $jctTotals = $result->getExtensionAttributes()->getJctTotals();
        $model->setJctTotals(json_encode($jctTotals->getData()));
        $model->setOrderId($result->getEntityId());

        $resourceModel = $this->magentojSalesOrderResourceModelFactory->create();
        $resourceModel->save($model);

        return $result;
    }

    /**
     * @param OrderRepositoryInterface $subject
     * @param OrderInterface $result
     * @return OrderInterface
     */
    public function afterGet(
        OrderRepositoryInterface $subject,
        OrderInterface $result
    ) {
        $existingOrder = $this->getSalesOrderByOrderId($result->getEntityId());

        if (!$existingOrder->getJctTotals()) {
            return $result;
        }
        
        $orderExtension = $result->getExtensionAttributes();
        $jctTotals = $this->jctTotalsInterfaceFactory->create(
            [
                'data' => json_decode($existingOrder->getJctTotals(), true)
            ]
        );
        $orderExtension->setJctTotals($jctTotals);

        $result->setExtensionAttributes($orderExtension);

        return $result;
    }

    /**
     * @param OrderRepositoryInterface $subject
     * @param OrderSearchResultInterface $result
     * @return mixed
     */
    public function afterGetList(
        OrderRepositoryInterface $subject,
        OrderSearchResultInterface $result
    ) {
        foreach ($result->getItems() as $order) {
            $this->afterGet($subject, $order);
        }

        return $result;
    }

    /**
     * @param int $orderId
     * @return SalesOrder
     */
    private function getSalesOrderByOrderId(int $orderId)
    {
        $collection = $this->magentojSalesOrderCollectionFactory->create();

        return $collection
            ->addFieldToFilter('order_id', $orderId)
            ->getFirstItem();
    }
}
