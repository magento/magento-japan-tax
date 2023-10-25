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
use Magentoj\JapaneseConsumptionTax\Model\QuoteAddress;
use Magentoj\JapaneseConsumptionTax\Model\QuoteAddressFactory as ModelFactory;
use Magentoj\JapaneseConsumptionTax\Model\ResourceModel\QuoteAddressFactory as ResourceModelFactory;
use Magentoj\JapaneseConsumptionTax\Model\ResourceModel\QuoteAddress\CollectionFactory;

class AddJctToQuoteAddress
{
    protected ModelFactory $magentojQuoteAddressModelFactory;

    protected ResourceModelFactory $magentojQuoteAddressResourceModelFactory;

    protected CollectionFactory $magentojQuoteAddressCollectionFactory;

    protected JctTotalsInterfaceFactory $jctTotalsInterfaceFactory;

    /**
     * AddJctToQuoteAddress constructor.
     * @param ModelFactory $magentojQuoteAddressModelFactory
     * @param ResourceModelFactory $magentojQuoteAddressResourceModelFactory
     * @param CollectionFactory $magentojQuoteAddressCollectionFactory
     */
    public function __construct(
        ModelFactory $magentojQuoteAddressModelFactory,
        ResourceModelFactory $magentojQuoteAddressResourceModelFactory,
        CollectionFactory $magentojQuoteAddressCollectionFactory,
        JctTotalsInterfaceFactory $jctTotalsInterfaceFactory
    ) {
        $this->magentojQuoteAddressModelFactory = $magentojQuoteAddressModelFactory;
        $this->magentojQuoteAddressResourceModelFactory = $magentojQuoteAddressResourceModelFactory;
        $this->magentojQuoteAddressCollectionFactory = $magentojQuoteAddressCollectionFactory;
        $this->jctTotalsInterfaceFactory = $jctTotalsInterfaceFactory;
    }

    /**
     * @param int $addressId
     * @return QuoteAddress
     */
    protected function getQuoteAddressByAddressId(int $addressId)
    {
        $collection = $this->magentojQuoteAddressCollectionFactory->create();

        return $collection
            ->addFieldToFilter('address_id', $addressId)
            ->getFirstItem();
    }
}
