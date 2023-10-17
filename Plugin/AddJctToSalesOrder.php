<?php

namespace Magentoj\JapaneseConsumptionTax\Plugin;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Model\Order;
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
        return $this->addJctToOrder($result);
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
     * @param Order $subject
     * @param Order $result
     * @return Order
     */
    public function afterLoad(
        Order $subject,
        Order $result
    ) {
        return $this->addJctToOrder($result);
    }

    /**
     * @param OrderInterface $order
     * @return OrderInterface
     */
    private function addJctToOrder(OrderInterface $order)
    {
        $existingOrder = $this->getSalesOrderByOrderId($order->getEntityId());

        if (!$existingOrder->getJctTotals()) {
            return $order;
        }

        $orderExtension = $order->getExtensionAttributes();
        $jctTotals = $this->jctTotalsInterfaceFactory->create(
            [
                'data' => json_decode($existingOrder->getJctTotals(), true)
            ]
        );
        $orderExtension->setJctTotals($jctTotals);

        $order->setExtensionAttributes($orderExtension);

        return $order;
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
