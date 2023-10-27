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

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class OrderRepository extends AddJctToSalesOrder
{
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
}
