<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Japan\Tax\Api\Data;

/**
 * Tax details interface.
 * @api
 * @since 100.0.2
 */
interface InvoiceTaxInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    /**
     * Get subtotal
     *
     * @return float
     */
    public function getSubtotal();

    /**
     * Set subtotal
     *
     * @param float $subtotal
     * @return $this
     */
    public function setSubtotal($subtotal);

    /**
     * Get tax amount
     *
     * @return float
     */
    public function getTaxAmount();

    /**
     * Set tax amount
     *
     * @param float $taxAmount
     * @return $this
     */
    public function setTaxAmount($taxAmount);

    /**
     * Get discount amount
     *
     * @return float
     */
    public function getDiscountTaxCompensationAmount();

    /**
     * Set discount amount
     *
     * @param float $discountTaxCompensationAmount
     * @return $this
     */
    public function setDiscountTaxCompensationAmount($discountTaxCompensationAmount);

    /**
     * Get applied taxes
     *
     * @return \Japan\Tax\Api\Data\AppliedTaxInterface[] | null
     */
    public function getAppliedTaxes();

    /**
     * Set applied taxes
     *
     * @param \Japan\Tax\Api\Data\AppliedTaxInterface[] $appliedTaxes
     * @return $this
     */
    public function setAppliedTaxes(array $appliedTaxes = null);

    /**
     * Get TaxDetails items
     *
     * @return \Japan\Tax\Api\Data\InvoiceTaxBlockInterface[] | null
     */
    public function getBlocks();

    /**
     * Set TaxDetails items
     *
     * @param \Japan\Tax\Api\Data\InvoiceTaxBlockInterface[] $items
     * @return $this
     */
    public function setBlocks(array $blocks = null);
}
