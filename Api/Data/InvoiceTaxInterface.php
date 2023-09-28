<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magentoj\JapaneseConsumptionTax\Api\Data;

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
     * @return \Magentoj\JapaneseConsumptionTax\Api\Data\AppliedTaxInterface[] | null
     */
    public function getAppliedTaxes();

    /**
     * Set applied taxes
     *
     * @param \Magentoj\JapaneseConsumptionTax\Api\Data\AppliedTaxInterface[] $appliedTaxes
     * @return $this
     */
    public function setAppliedTaxes(array $appliedTaxes = null);

    /**
     * Get blocks
     *
     * @return \Magentoj\JapaneseConsumptionTax\Api\Data\InvoiceTaxBlockInterface[] | null
     */
    public function getBlocks();

    /**
     * Set blocks
     *
     * @param \Magentoj\JapaneseConsumptionTax\Api\Data\InvoiceTaxBlockInterface[] $blocks
     * @return $this
     */
    public function setBlocks(array $blocks = null);
}
