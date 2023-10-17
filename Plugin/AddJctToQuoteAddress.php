<?php

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
