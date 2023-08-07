<?php

namespace Japan\Tax\Model\InvoiceTax;

use Magento\Framework\Model\AbstractExtensibleModel;
use Japan\Tax\Api\Data\InvoiceTaxItemInterface;

/**
 * @codeCoverageIgnore
 */
class InvoiceTaxItem extends AbstractExtensibleModel implements InvoiceTaxItemInterface
{
    /**#@+
     * Constants defined for keys of array, makes typos less likely
     */
    const KEY_CODE                 = 'code';
    const KEY_TYPE                 = 'type';
    const KEY_TAX_PERCENT          = 'tax_percent';
    const KEY_PRICE                = 'price';
    const KEY_QUANTITY             = 'quantity';
    const KEY_ROW_TOTAL            = 'row_total';
    const KEY_DISCOUNT_AMOUNT      = 'discount_amount';
    const KEY_APPLIED_TAXES        = 'applied_taxes';
    const KEY_ASSOCIATED_ITEM_CODE = 'associated_item_code';
    /**#@-*/

    /**
     * {@inheritdoc}
     */
    public function getCode()
    {
        return $this->getData(self::KEY_CODE);
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return $this->getData(self::KEY_TYPE);
    }

    /**
     * {@inheritdoc}
     */
    public function getTaxPercent()
    {
        return $this->getData(self::KEY_TAX_PERCENT);
    }

    /**
     * {@inheritdoc}
     */
    public function getPrice()
    {
        return $this->getData(self::KEY_PRICE);
    }

    /**
     * {@inheritdoc}
     */
    public function getRowTotal()
    {
        return $this->getData(self::KEY_ROW_TOTAL);
    }

    /**
     * {@inheritdoc}
     */
    public function getDiscountAmount()
    {
        return $this->getData(self::KEY_DISCOUNT_AMOUNT);
    }

    /**
     * {@inheritdoc}
     */
    public function getAssociatedItemCode()
    {
        return $this->getData(self::KEY_ASSOCIATED_ITEM_CODE);
    }

    /**
     * Set code (sku or shipping code)
     *
     * @param string $code
     * @return $this
     */
    public function setCode($code)
    {
        return $this->setData(self::KEY_CODE, $code);
    }

    /**
     * Set type (shipping, product, weee, gift wrapping, etc
     *
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        return $this->setData(self::KEY_TYPE, $type);
    }

    /**
     * Set tax_percent
     *
     * @param float $taxPercent
     * @return $this
     */
    public function setTaxPercent($taxPercent)
    {
        return $this->setData(self::KEY_TAX_PERCENT, $taxPercent);
    }

    /**
     * Set price
     *
     * @param float $price
     * @return $this
     */
    public function setPrice($price)
    {
        return $this->setData(self::KEY_PRICE, $price);
    }

    /**
     * @inheritdoc
     */
    public function getQuantity()
    {
        return $this->getData(self::KEY_QUANTITY);
    }

    /**
     * @inheritdoc
     */
    public function setQuantity($quantity)
    {
        return $this->setData(self::KEY_QUANTITY, $quantity);
    }

    /**
     * Set row total
     *
     * @param float $rowTotal
     * @return $this
     */
    public function setRowTotal($rowTotal)
    {
        return $this->setData(self::KEY_ROW_TOTAL, $rowTotal);
    }

    /**
     * Set discount amount
     *
     * @param float $discountAmount
     * @return $this
     */
    public function setDiscountAmount($discountAmount)
    {
        return $this->setData(self::KEY_DISCOUNT_AMOUNT, $discountAmount);
    }

    /**
     * Set associated item code
     *
     * @param int $associatedItemCode
     * @return $this
     */
    public function setAssociatedItemCode($associatedItemCode)
    {
        return $this->setData(self::KEY_ASSOCIATED_ITEM_CODE, $associatedItemCode);
    }
}
