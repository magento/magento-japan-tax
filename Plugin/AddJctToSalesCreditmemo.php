<?php
/**
 * This file is part of Japanese Consumption Tax Extension For Magento2 the  project.
 *
 * Copyright (c) 2023 Adobe (or other copyright holders)
 *
 * For the full copyright and license information, please view the OSL-3.0
 * license that is bundled with this source code in the file LICENSE, or
 * at https://opensource.org/licenses/OSL-3.0
 */
namespace Magentoj\JapaneseConsumptionTax\Plugin;

use Magentoj\JapaneseConsumptionTax\Api\Data\JctTotalsInterfaceFactory;
use Magentoj\JapaneseConsumptionTax\Model\SalesCreditmemo;
use Magentoj\JapaneseConsumptionTax\Model\SalesCreditmemoFactory as ModelFactory;
use Magentoj\JapaneseConsumptionTax\Model\ResourceModel\SalesCreditmemoFactory as ResourceModelFactory;
use Magentoj\JapaneseConsumptionTax\Model\ResourceModel\SalesCreditmemo\CollectionFactory;

class AddJctToSalesCreditmemo
{
    protected ModelFactory $magentojSalesCreditmemoModelFactory;

    protected ResourceModelFactory $magentojSalesCreditmemoResourceModelFactory;

    protected CollectionFactory $magentojSalesCreditmemoCollectionFactory;

    protected JctTotalsInterfaceFactory $jctTotalsInterfaceFactory;

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
     * @param int $creditmemoId
     * @return SalesCreditmemo
     */
    protected function getSalesCreditmemoByCreditmemoId(int $creditmemoId)
    {
        $collection = $this->magentojSalesCreditmemoCollectionFactory->create();

        return $collection
            ->addFieldToFilter('creditmemo_id', $creditmemoId)
            ->getFirstItem();
    }
}
