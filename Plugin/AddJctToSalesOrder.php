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

use Magento\Sales\Api\Data\OrderInterface;
use Magentoj\JapaneseConsumptionTax\Api\Data\JctTotalsInterfaceFactory;
use Magentoj\JapaneseConsumptionTax\Model\SalesOrder;
use Magentoj\JapaneseConsumptionTax\Model\SalesOrderFactory as ModelFactory;
use Magentoj\JapaneseConsumptionTax\Model\ResourceModel\SalesOrderFactory as ResourceModelFactory;
use Magentoj\JapaneseConsumptionTax\Model\ResourceModel\SalesOrder\CollectionFactory;

class AddJctToSalesOrder
{
    protected ModelFactory $magentojSalesOrderModelFactory;

    protected ResourceModelFactory $magentojSalesOrderResourceModelFactory;

    protected CollectionFactory $magentojSalesOrderCollectionFactory;

    protected JctTotalsInterfaceFactory $jctTotalsInterfaceFactory;

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
     * @param OrderInterface $order
     * @return OrderInterface
     */
    protected function addJctToOrder(OrderInterface $order)
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
    protected function getSalesOrderByOrderId(int $orderId)
    {
        $collection = $this->magentojSalesOrderCollectionFactory->create();

        return $collection
            ->addFieldToFilter('order_id', $orderId)
            ->getFirstItem();
    }
}
