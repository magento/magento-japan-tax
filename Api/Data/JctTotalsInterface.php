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
 * JCT totals interface.
 * @api
 * @since 100.0.2
 */
interface JctTotalsInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    /**
     * Get base subtotal subject to 10% JCT
     *
     * @return int|null
     */
    public function getBaseSubtotalExclJct10();

    /**
     * Get base subtotal subject to 8% JCT
     *
     * @return int|null
     */
    public function getBaseSubtotalExclJct8();

    /**
     * Get base subtotal subject to 10% JCT (Incl. Tax)
     *
     * @return int|null
     */
    public function getBaseSubtotalInclJct10();

    /**
     * Get base subtotal subject to 8% JCT (Incl. Tax)
     *
     * @return int|null
     */
    public function getBaseSubtotalInclJct8();

    /**
     * Get base 10% JCT amount
     *
     * @return int|null
     */
    public function getBaseJct10Amount();

    /**
     * Get base 8% JCT amount
     *
     * @return int|null
     */
    public function getBaseJct8Amount();

    /**
     * Get subtotal subject to 10% JCT
     *
     * @return int|null
     */
    public function getSubtotalExclJct10();

    /**
     * Get subtotal subject to 8% JCT
     *
     * @return int|null
     */
    public function getSubtotalExclJct8();

    /**
     * Get subtotal subject to 10% JCT (Incl. Tax)
     *
     * @return int|null
     */
    public function getSubtotalInclJct10();

    /**
     * Get subtotal subject to 8% JCT (Incl. Tax)
     *
     * @return int|null
     */
    public function getSubtotalInclJct8();

    /**
     * Get 10% JCT amount
     *
     * @return int|null
     */
    public function getJct10Amount();

    /**
     * Get 8% JCT amount
     *
     * @return int|null
     */
    public function getJct8Amount();

    /**
     * Get isTaxIncluded
     *
     * @return boolean|null
     */
    public function getIsTaxIncluded();

    /**
     * Set base subtotal subject to 10% JCT
     * @param int $amount
     * @return $this
     */
    public function setBaseSubtotalExclJct10(int $amount);

    /**
     * Set base subtotal subject to 8% JCT
     * @param int $amount
     * @return $this
     */
    public function setBaseSubtotalExclJct8(int $amount);

    /**
     * Set base subtotal subject to 10% JCT (Incl. Tax)
     * @param int $amount
     * @return $this
     */
    public function setBaseSubtotalInclJct10(int $amount);

    /**
     * Set base subtotal subject to 8% JCT (Incl. Tax)
     * @param int $amount
     * @return $this
     */
    public function setBaseSubtotalInclJct8(int $amount);

    /**
     * Set base 10% JCT amount
     * @param int $amount
     * @return $this
     */
    public function setBaseJct10Amount(int $amount);

    /**
     * Set base 8% JCT amount
     * @param int $amount
     * @return $this
     */
    public function setBaseJct8Amount(int $amount);

    /**
     * Set subtotal subject to 10% JCT
     * @param int $amount
     * @return $this
     */
    public function setSubtotalExclJct10(int $amount);

    /**
     * Set subtotal subject to 8% JCT
     * @param int $amount
     * @return $this
     */
    public function setSubtotalExclJct8(int $amount);

    /**
     * Set subtotal subject to 10% JCT (Incl. Tax)
     * @param int $amount
     * @return $this
     */
    public function setSubtotalInclJct10(int $amount);

    /**
     * Set subtotal subject to 8% JCT (Incl. Tax)
     * @param int $amount
     * @return $this
     */
    public function setSubtotalInclJct8(int $amount);

    /**
     * Set 10% JCT amount
     * @param int $amount
     * @return $this
     */
    public function setJct10Amount(int $amount);

    /**
     * Set 8% JCT amount
     * @param int $amount
     * @return $this
     */
    public function setJct8Amount(int $amount);

    /**
     * Set isTaxIncluded
     * @param boolean $isTaxIncluded
     * @return $this
     */
    public function setIsTaxIncluded(bool $isTaxIncluded);
}
