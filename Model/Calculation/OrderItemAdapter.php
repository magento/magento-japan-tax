<?php

namespace Japan\Tax\Model\Calculation;

use Magento\Sales\Model\Order\Item;
use Magento\Tax\Api\Data\QuoteDetailsItemInterface;

class OrderItemAdapter implements QuoteDetailsItemInterface
{
    /**
     * Order Item
     *
     * @var \Magento\Sales\Model\Order\Item;
     */
    private $orderItem;

    public function __construct(Item $orderItem) {
        $this->orderItem = $orderItem;
    }

    /**
     * {@inheritdoc}
     */
    public function getCode()
    {
        \Exception('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return $this->orderItem->getProductType();
    }

    /**
     * {@inheritdoc}
     */
    public function getTaxClassKey()
    {
        throw \Exception('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function getUnitPrice()
    {
        return $this->orderItem->getPrice();
    }

    /**
     * {@inheritdoc}
     */
    public function getQuantity()
    {
        return $this->orderItem->getQty();
    }

    /**
     * {@inheritdoc}
     */
    public function getIsTaxIncluded()
    {
        throw \Exception('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function getShortDescription()
    {
        throw \Exception('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function getDiscountAmount()
    {
        return $this->orderItem->getDiscountAmount();
    }

    /**
     * {@inheritdoc}
     */
    public function getParentCode()
    {
        throw \Exception('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function getAssociatedItemCode()
    {
        throw \Exception('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function getTaxClassId()
    {
        throw \Exception('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensionAttributes()
    {
        throw \Exception('Not implemented');
    }

    /**
     * Set code (sku or shipping code)
     *
     * @param string $code
     * @return $this
     */
    public function setCode($code)
    {
        throw \Exception('Not implemented');
    }

    /**
     * Set type (e.g., shipping, product, wee, gift wrapping, etc.)
     *
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        throw \Exception('Not implemented');
    }

    /**
     * Set tax class key
     *
     * @param \Magento\Tax\Api\Data\TaxClassKeyInterface $taxClassKey
     * @return $this
     */
    public function setTaxClassKey(\Magento\Tax\Api\Data\TaxClassKeyInterface $taxClassKey = null)
    {
        throw \Exception('Not implemented');
    }

    /**
     * Set unit price
     *
     * @param float $unitPrice
     * @return $this
     */
    public function setUnitPrice($unitPrice)
    {
        throw \Exception('Not implemented');
    }

    /**
     * Set quantity
     *
     * @param float $quantity
     * @return $this
     */
    public function setQuantity($quantity)
    {
        throw \Exception('Not implemented');
    }

    /**
     * Set whether the tax is included in the unit price and row total
     *
     * @param bool $isTaxIncluded
     * @return $this
     */
    public function setIsTaxIncluded($isTaxIncluded)
    {
        throw \Exception('Not implemented');
    }

    /**
     * Set short description
     *
     * @param string $shortDescription
     * @return $this
     */
    public function setShortDescription($shortDescription)
    {
        throw \Exception('Not implemented');
    }

    /**
     * Set discount amount
     *
     * @param float $discountAmount
     * @return $this
     */
    public function setDiscountAmount($discountAmount)
    {
        throw \Exception('Not implemented');
    }

    /**
     * Set parent code
     *
     * @param string $parentCode
     * @return $this
     */
    public function setParentCode($parentCode)
    {
        throw \Exception('Not implemented');
    }

    /**
     * Set associated item code
     *
     * @param int $associatedItemCode
     * @return $this
     */
    public function setAssociatedItemCode($associatedItemCode)
    {
        throw \Exception('Not implemented');
    }

    /**
     * Set associated item tax class id
     *
     * @param int $taxClassId
     * @return $this
     */
    public function setTaxClassId($taxClassId)
    {
        throw \Exception('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function setExtensionAttributes(\Magento\Tax\Api\Data\QuoteDetailsItemExtensionInterface $extensionAttributes)
    {
        throw \Exception('Not implemented');
    }
}
