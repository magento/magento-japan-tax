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
interface InvoiceTaxBlockInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    /**
     * Get tax_percent
     *
     * @return float
     */
    public function getTaxPercent();

    /**
     * Set tax_percent
     *
     * @param float $taxPercent
     * @return $this
     */
    public function setTaxPercent($taxPercent);

    /**
     * Get row total
     *
     * @return float
     */
    public function getTotal();

    /**
     * Set row total
     *
     * @param float $rowTotal
     * @return $this
     */
    public function setTotal($rowTotal);

    /**
     * Get row total including tax
     *
     * @return float
     */
    public function getTotalInclTax();

    /**
     * Set row total including tax
     *
     * @param float $rowTotalInclTax
     * @return $this
     */
    public function setTotalInclTax($rowTotalInclTax);

    /**
     * Get row tax amount
     *
     * @return float
     */
    public function getTax();

    /**
     * Set row tax amount
     *
     * @param float $rowTax
     * @return $this
     */
    public function setTax($rowTax);

    /**
     * Get taxable amount
     *
     * @return float
     */
    public function getTaxableAmount();

    /**
     * Set taxable amount
     *
     * @param float $taxableAmount
     * @return $this
     */
    public function setTaxableAmount($taxableAmount);

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
     * Get discount tax compensation amount
     *
     * @return float
     */
    public function getDiscountTaxCompensationAmount();

    /**
     * Set discount tax compensation amount
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
     * Get items
     *
     * @return \Japan\Tax\Api\Data\InvoiceTaxItemInterface[] | null
     */
    public function getItems();

    /**
     * Set items
     *
     * @param \Japan\Tax\Api\Data\InvoiceTaxItemInterface[] $appliedTaxes
     * @return $this
     */
    public function setItems(array $items = null);


}