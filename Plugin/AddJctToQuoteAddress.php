<?php

namespace Magentoj\JapaneseConsumptionTax\Plugin;

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartSearchResultsInterface;
use Magentoj\JapaneseConsumptionTax\Api\Data\JctTotalsInterfaceFactory;
use Magentoj\JapaneseConsumptionTax\Model\QuoteAddress;
use Magentoj\JapaneseConsumptionTax\Model\QuoteAddressFactory as ModelFactory;
use Magentoj\JapaneseConsumptionTax\Model\ResourceModel\QuoteAddressFactory as ResourceModelFactory;
use Magentoj\JapaneseConsumptionTax\Model\ResourceModel\QuoteAddress\CollectionFactory;

class AddJctToQuoteAddress
{
    private ModelFactory $magentojQuoteAddressModelFactory;

    private ResourceModelFactory $magentojQuoteAddressResourceModelFactory;

    private CollectionFactory $magentojQuoteAddressCollectionFactory;

    private JctTotalsInterfaceFactory $jctTotalsInterfaceFactory;

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
     * @param CartRepositoryInterface $subject
     * @param $result
     * @param CartInterface $quote
     * @return void
     */
    public function afterSave(
        CartRepositoryInterface $subject,
        $result,
        CartInterface $quote
    ) {
        $address = $quote->isVirtual() ? $quote->getBillingAddress() : $quote->getShippingAddress();

        $model = $this->getQuoteAddressByAddressId($address->getAddressId());

        $jctTotals = $address->getJctTotals();
        $model->setJctTotals(json_encode($jctTotals->getData()));
        $model->setAddressId($address->getAddressId());

        $resourceModel = $this->magentojQuoteAddressResourceModelFactory->create();
        $resourceModel->save($model);
    }

    /**
     * @param CartRepositoryInterface $subject
     * @param CartInterface $result
     * @return CartInterface
     */
    public function afterGet(
        CartRepositoryInterface $subject,
        CartInterface $result
    ) {
        $address = $result->isVirtual() ? $result->getBillingAddress() : $result->getShippingAddress();

        $existingAddress = $this->getQuoteAddressByAddressId($address->getAddressId());

        if (!$existingAddress->getJctTotals()) {
            return $result;
        }

        $jctTotals = $this->jctTotalsInterfaceFactory->create(
            [
                'data' => json_decode($existingAddress->getJctTotals(), true)
            ]
        );
        $result->getShippingAddress()->setJctTotals($jctTotals);

        return $result;
    }

    /**
     * @param CartRepositoryInterface $subject
     * @param CartSearchResultsInterface $result
     * @return mixed
     */
    public function afterGetList(
        CartRepositoryInterface $subject,
        CartSearchResultsInterface $result
    ) {
        foreach ($result->getItems() as $quote) {
            $this->afterGet($subject, $quote);
        }

        return $result;
    }

    /**
     * @param int $addressId
     * @return QuoteAddress
     */
    private function getQuoteAddressByAddressId(int $addressId)
    {
        $collection = $this->magentojQuoteAddressCollectionFactory->create();

        return $collection
            ->addFieldToFilter('address_id', $addressId)
            ->getFirstItem();
    }
}
