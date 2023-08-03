<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Japan\Tax\Api\Data;

/**
 * Tax details items interface.
 * @api
 * @since 100.0.2
 */
interface InvoiceTaxItemInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    /**
     * Get code (sku or shipping code)
     *
     * @return string|null
     */
    public function getCode();

    /**
     * Set code (sku or shipping code)
     *
     * @param string $code
     * @return $this
     */
    public function setCode($code);

    /**
     * Get type (shipping, product, weee, gift wrapping, etc
     *
     * @return string|null
     */
    public function getType();

    /**
     * Set type (shipping, product, weee, gift wrapping, etc
     *
     * @param string $type
     * @return $this
     */
    public function setType($type);

    /**
     * Get price
     *
     * @return float
     */
    public function getPrice();

    /**
     * Set price
     *
     * @param float $price
     * @return $this
     */
    public function setPrice($price);


    /**
     * Set quantity.
     *
     * @param float $quantity
     * @return $this
     * @since 102.0.0
     */
    public function setQuantity($quantity);

    /**
     * Get quantity.
     *
     * @return float
     * @since 102.0.0
     */
    public function getQuantity();

    /**
     * Get row total
     *
     * @return float
     */
    public function getRowTotal();

    /**
     * Set row total
     *
     * @param float $rowTotal
     * @return $this
     */
    public function setRowTotal($rowTotal);

    /**
     * Get discount amount
     *
     * @return float
     */
    public function getDiscountAmount();

    /**
     * Set discount amount
     *
     * @param float $discountAmount
     * @return $this
     */
    public function setDiscountAmount($discountAmount);

    /**
     * Return associated item code if this item is associated with another item, null otherwise
     *
     * @return int|null
     */
    public function getAssociatedItemCode();

    /**
     * Set associated item code
     *
     * @param int $associatedItemCode
     * @return $this
     */
    public function setAssociatedItemCode($associatedItemCode);
}
