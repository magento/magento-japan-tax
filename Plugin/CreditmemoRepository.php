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

use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\CreditmemoSearchResultInterface;

class CreditmemoRepository extends AddJctToSalesCreditmemo
{
    /**
     * @param CreditmemoRepositoryInterface $subject
     * @param CreditmemoInterface $result
     * @return CreditmemoInterface
     */
    public function afterSave(
        CreditmemoRepositoryInterface $subject,
        CreditmemoInterface $result
    ) {
        $model = $this->getSalesCreditmemoByCreditmemoId($result->getEntityId());

        $jctTotals = $result->getExtensionAttributes()->getJctTotals();

        if ($model->getData() || !$jctTotals) {
            return $result;
        }

        $model->setJctTotals(json_encode($jctTotals->getData()));
        $model->setCreditmemoId($result->getEntityId());

        $resourceModel = $this->magentojSalesCreditmemoResourceModelFactory->create();
        $resourceModel->save($model);

        return $result;
    }

    /**
     * @param CreditmemoRepositoryInterface $subject
     * @param CreditmemoInterface $result
     * @return CreditmemoInterface
     */
    public function afterGet(
        CreditmemoRepositoryInterface $subject,
        CreditmemoInterface $result
    ) {
        $existingCreditmemo = $this->getSalesCreditmemoByCreditmemoId($result->getEntityId());

        if (!$existingCreditmemo->getJctTotals()) {
            return $result;
        }

        $creditmemoExtension = $result->getExtensionAttributes();
        $jctTotals = $this->jctTotalsInterfaceFactory->create(
            [
                'data' => json_decode($existingCreditmemo->getJctTotals(), true)
            ]
        );
        $creditmemoExtension->setJctTotals($jctTotals);

        $result->setExtensionAttributes($creditmemoExtension);

        return $result;
    }

    /**
     * @param CreditmemoRepositoryInterface $subject
     * @param CreditmemoSearchResultInterface $result
     * @return mixed
     */
    public function afterGetList(
        CreditmemoRepositoryInterface $subject,
        CreditmemoSearchResultInterface $result
    ) {
        foreach ($result->getItems() as $creditmemo) {
            $this->afterGet($subject, $creditmemo);
        }

        return $result;
    }
}
