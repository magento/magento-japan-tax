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
namespace Magentoj\JapaneseConsumptionTax\Model\Calculation;

use Magento\Sales\Api\Data\InvoiceItemInterface;
use Magento\Sales\Api\Data\CreditmemoItemInterface;
use Magento\Tax\Api\Data\QuoteDetailsItemInterface;

class OrderItemAdapter implements QuoteDetailsItemInterface
{
    /**
     * Invoice or Creditmemo Item
     *
     * @var InvoiceItemInterface|CreditmemoItemInterface;
     */
    private $item;

    /**
     * @param InvoiceItemInterface|CreditmemoItemInterface $item
     */
    public function __construct($item)
    {
        $this->item = $item;
    }

    /**
     * @inheritdoc
     */
    public function getCode()
    {
        return $this->item->getSku();
    }

    /**
     * @inheritdoc
     */
    public function getType()
    {
        return $this->item->getProductType();
    }

    /**
     * @inheritdoc
     */
    public function getTaxClassKey()
    {
        throw new \Exception('Not implemented');
    }

    /**
     * @inheritdoc
     */
    public function getUnitPrice()
    {
        return $this->item->getPrice();
    }

    /**
     * @inheritdoc
     */
    public function getQuantity()
    {
        return $this->item->getQty();
    }

    /**
     * @inheritdoc
     */
    public function getIsTaxIncluded()
    {
        throw new \Exception('Not implemented');
    }

    /**
     * @inheritdoc
     */
    public function getShortDescription()
    {
        throw new \Exception('Not implemented');
    }

    /**
     * @inheritdoc
     */
    public function getDiscountAmount()
    {
        return $this->item->getDiscountAmount();
    }

    /**
     * @inheritdoc
     */
    public function getParentCode()
    {
        throw new \Exception('Not implemented');
    }

    /**
     * @inheritdoc
     */
    public function getAssociatedItemCode()
    {
        throw new \Exception('Not implemented');
    }

    /**
     * @inheritdoc
     */
    public function getTaxClassId()
    {
        throw new \Exception('Not implemented');
    }

    /**
     * @inheritdoc
     */
    public function getExtensionAttributes()
    {
        throw new \Exception('Not implemented');
    }

    /**
     * @inheritdoc
     */
    public function setCode($code)
    {
        throw new \Exception('Not implemented');
    }

    /**
     * @inheritdoc
     */
    public function setType($type)
    {
        throw new \Exception('Not implemented');
    }

    /**
     * @inheritdoc
     */
    public function setTaxClassKey(\Magento\Tax\Api\Data\TaxClassKeyInterface $taxClassKey = null)
    {
        throw new \Exception('Not implemented');
    }

    /**
     * Set unit price
     *
     * @param float $unitPrice
     * @return $this
     */
    public function setUnitPrice($unitPrice)
    {
        throw new \Exception('Not implemented');
    }

    /**
     * Set quantity
     *
     * @param float $quantity
     * @return $this
     */
    public function setQuantity($quantity)
    {
        throw new \Exception('Not implemented');
    }

    /**
     * @inheritdoc
     */
    public function setIsTaxIncluded($isTaxIncluded)
    {
        throw new \Exception('Not implemented');
    }

    /**
     * Set short description
     *
     * @param string $shortDescription
     * @return $this
     */
    public function setShortDescription($shortDescription)
    {
        throw new \Exception('Not implemented');
    }

    /**
     * @inheritdoc
     */
    public function setDiscountAmount($discountAmount)
    {
        throw new \Exception('Not implemented');
    }

    /**
     * Set parent code
     *
     * @param string $parentCode
     * @return $this
     */
    public function setParentCode($parentCode)
    {
        throw new \Exception('Not implemented');
    }

    /**
     * @inheritdoc
     */
    public function setAssociatedItemCode($associatedItemCode)
    {
        throw new \Exception('Not implemented');
    }

    /**
     * @inheritdoc
     */
    public function setTaxClassId($taxClassId)
    {
        throw new \Exception('Not implemented');
    }

    /**
     * @inheritdoc
     */
    public function setExtensionAttributes(\Magento\Tax\Api\Data\QuoteDetailsItemExtensionInterface $extensionAttributes)
    {
        throw new \Exception('Not implemented');
    }
}
