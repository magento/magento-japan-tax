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

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartSearchResultsInterface;

class CartRepository extends AddJctToQuoteAddress
{
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

        if (!$jctTotals) {
            return;
        }

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
        // skip this method in case
        // 1. the address id is missing when the cart become emptied.
        // 2. the address has jct totals already.
        if ($address->getAddressId() === null || $address->getJctTotals()) {
            return $result;
        }

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
}
