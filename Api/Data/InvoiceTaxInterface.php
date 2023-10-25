<?php
/**
 * This file is part of Japanese Consumption Tax Extension For Magento2 the  project.
 *
 * Copyright (c) 2023 Adobe (or other copyright holders)
 *
 * For the full copyright and license information, please view the OSL-3.0
 * license that is bundled with this source code in the file LICENSE, or
 * at https://opensource.org/licenses/OSL-3.0
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
