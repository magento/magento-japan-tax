<?php

namespace Magentoj\JapaneseConsumptionTax\Plugin;

use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\CreditmemoSearchResultInterface;
use Magentoj\JapaneseConsumptionTax\Api\Data\JctTotalsInterfaceFactory;
use Magentoj\JapaneseConsumptionTax\Model\SalesCreditmemo;
use Magentoj\JapaneseConsumptionTax\Model\SalesCreditmemoFactory as ModelFactory;
use Magentoj\JapaneseConsumptionTax\Model\ResourceModel\SalesCreditmemoFactory as ResourceModelFactory;
use Magentoj\JapaneseConsumptionTax\Model\ResourceModel\SalesCreditmemo\CollectionFactory;

class AddJctToSalesCreditmemo
{
    private ModelFactory $magentojSalesCreditmemoModelFactory;

    private ResourceModelFactory $magentojSalesCreditmemoResourceModelFactory;

    private CollectionFactory $magentojSalesCreditmemoCollectionFactory;

    private JctTotalsInterfaceFactory $jctTotalsInterfaceFactory;

    /**
     * AddJctToSalesCreditmemo constructor.
     * @param ModelFactory $magentojSalesCreditmemoModelFactory
     * @param ResourceModelFactory $magentojSalesCreditmemoResourceModelFactory
     * @param CollectionFactory $magentojSalesCreditmemoCollectionFactory
     * @param JctTotalsInterfaceFactory $jctTotalsInterfaceFactory
     */
    public function __construct(
        ModelFactory $magentojSalesCreditmemoModelFactory,
        ResourceModelFactory $magentojSalesCreditmemoResourceModelFactory,
        CollectionFactory $magentojSalesCreditmemoCollectionFactory,
        JctTotalsInterfaceFactory $jctTotalsInterfaceFactory
    ) {
        $this->magentojSalesCreditmemoModelFactory = $magentojSalesCreditmemoModelFactory;
        $this->magentojSalesCreditmemoResourceModelFactory = $magentojSalesCreditmemoResourceModelFactory;
        $this->magentojSalesCreditmemoCollectionFactory = $magentojSalesCreditmemoCollectionFactory;
        $this->jctTotalsInterfaceFactory = $jctTotalsInterfaceFactory;
    }

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

        if ($model->getData()) {
            return $result;
        }

        $jctTotals = $result->getExtensionAttributes()->getJctTotals();
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

    /**
     * @param int $creditmemoId
     * @return SalesCreditmemo
     */
    private function getSalesCreditmemoByCreditmemoId(int $creditmemoId)
    {
        $collection = $this->magentojSalesCreditmemoCollectionFactory->create();

        return $collection
            ->addFieldToFilter('creditmemo_id', $creditmemoId)
            ->getFirstItem();
    }
}
