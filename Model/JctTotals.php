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
namespace Magentoj\JapaneseConsumptionTax\Model;

use Magento\Framework\Model\AbstractExtensibleModel;
use Magentoj\JapaneseConsumptionTax\Api\Data\JctTotalsInterface;

/**
 * @codeCoverageIgnore
 */
class JctTotals extends AbstractExtensibleModel implements JctTotalsInterface
{
    /**#@+
     * Constants defined for keys of array, makes typos less likely
     */
    public const KEY_BASE_SUBTOTAL_EXCL_JCT_10 = 'base_subtotal_excl_jct_10';
    public const KEY_BASE_SUBTOTAL_EXCL_JCT_8  = 'base_subtotal_excl_jct_8';
    public const KEY_BASE_SUBTOTAL_INCL_JCT_10 = 'base_subtotal_incl_jct_10';
    public const KEY_BASE_SUBTOTAL_INCL_JCT_8  = 'base_subtotal_incl_jct_8';
    public const KEY_BASE_JCT_10_AMOUNT        = 'base_jct_10_amount';
    public const KEY_BASE_JCT_8_AMOUNT         = 'base_jct_8_amount';
    public const KEY_SUBTOTAL_EXCL_JCT_10      = 'subtotal_excl_jct_10';
    public const KEY_SUBTOTAL_EXCL_JCT_8       = 'subtotal_excl_jct_8';
    public const KEY_SUBTOTAL_INCL_JCT_10      = 'subtotal_incl_jct_10';
    public const KEY_SUBTOTAL_INCL_JCT_8       = 'subtotal_incl_jct_8';
    public const KEY_JCT_10_AMOUNT             = 'jct_10_amount';
    public const KEY_JCT_8_AMOUNT              = 'jct_8_amount';
    public const KEY_IS_TAX_INCLUDED           = 'is_tax_included';
    /**#@-*/

    /**
     * Get base subtotal subject to 10% JCT
     *
     * @return int|null
     */
    public function getBaseSubtotalExclJct10()
    {
        return $this->getData(self::KEY_BASE_SUBTOTAL_EXCL_JCT_10);
    }

    /**
     * Get base subtotal subject to 8% JCT
     *
     * @return int|null
     */
    public function getBaseSubtotalExclJct8()
    {
        return $this->getData(self::KEY_BASE_SUBTOTAL_EXCL_JCT_8);
    }

    /**
     * Get base subtotal subject to 10% JCT (Incl. Tax)
     *
     * @return int|null
     */
    public function getBaseSubtotalInclJct10()
    {
        return $this->getData(self::KEY_BASE_SUBTOTAL_INCL_JCT_10);
    }

    /**
     * Get base subtotal subject to 8% JCT (Incl. Tax)
     *
     * @return int|null
     */
    public function getBaseSubtotalInclJct8()
    {
        return $this->getData(self::KEY_BASE_SUBTOTAL_INCL_JCT_8);
    }

    /**
     * Get base 10% JCT amount
     *
     * @return int|null
     */
    public function getBaseJct10Amount()
    {
        return $this->getData(self::KEY_BASE_JCT_10_AMOUNT);
    }

    /**
     * Get base 8% JCT amount
     *
     * @return int|null
     */
    public function getBaseJct8Amount()
    {
        return $this->getData(self::KEY_BASE_JCT_8_AMOUNT);
    }

    /**
     * Get subtotal subject to 10% JCT
     *
     * @return int|null
     */
    public function getSubtotalExclJct10()
    {
        return $this->getData(self::KEY_SUBTOTAL_EXCL_JCT_10);
    }

    /**
     * Get subtotal subject to 8% JCT
     *
     * @return int|null
     */
    public function getSubtotalExclJct8()
    {
        return $this->getData(self::KEY_SUBTOTAL_EXCL_JCT_8);
    }

    /**
     * Get subtotal subject to 10% JCT (Incl. Tax)
     *
     * @return int|null
     */
    public function getSubtotalInclJct10()
    {
        return $this->getData(self::KEY_SUBTOTAL_INCL_JCT_10);
    }

    /**
     * Get subtotal subject to 8% JCT (Incl. Tax)
     *
     * @return int|null
     */
    public function getSubtotalInclJct8()
    {
        return $this->getData(self::KEY_SUBTOTAL_INCL_JCT_8);
    }

    /**
     * Get 10% JCT amount
     *
     * @return int|null
     */
    public function getJct10Amount()
    {
        return $this->getData(self::KEY_JCT_10_AMOUNT);
    }

    /**
     * Get 8% JCT amount
     *
     * @return int|null
     */
    public function getJct8Amount()
    {
        return $this->getData(self::KEY_JCT_8_AMOUNT);
    }

    /**
     * Get isTaxIncluded
     *
     * @return boolean|null
     */
    public function getIsTaxIncluded()
    {
        return $this->getData(self::KEY_IS_TAX_INCLUDED);
    }

    /**
     * Set base subtotal subject to 10% JCT
     * @param int $amount
     * @return $this
     */
    public function setBaseSubtotalExclJct10(int $amount)
    {
        return $this->setData(self::KEY_BASE_SUBTOTAL_EXCL_JCT_10, $amount);
    }

    /**
     * Set base subtotal subject to 8% JCT
     * @param int $amount
     * @return $this
     */
    public function setBaseSubtotalExclJct8(int $amount)
    {
        return $this->setData(self::KEY_BASE_SUBTOTAL_EXCL_JCT_8, $amount);
    }

    /**
     * Set base subtotal subject to 10% JCT (Incl. Tax)
     * @param int $amount
     * @return $this
     */
    public function setBaseSubtotalInclJct10(int $amount)
    {
        return $this->setData(self::KEY_BASE_SUBTOTAL_INCL_JCT_10, $amount);
    }

    /**
     * Set base subtotal subject to 8% JCT (Incl. Tax)
     * @param int $amount
     * @return $this
     */
    public function setBaseSubtotalInclJct8(int $amount)
    {
        return $this->setData(self::KEY_BASE_SUBTOTAL_INCL_JCT_8, $amount);
    }

    /**
     * Set base 10% JCT amount
     * @param int $amount
     * @return $this
     */
    public function setBaseJct10Amount(int $amount)
    {
        return $this->setData(self::KEY_BASE_JCT_10_AMOUNT, $amount);
    }

    /**
     * Set base 8% JCT amount
     * @param int $amount
     * @return $this
     */
    public function setBaseJct8Amount(int $amount)
    {
        return $this->setData(self::KEY_BASE_JCT_8_AMOUNT, $amount);
    }

    /**
     * Set subtotal subject to 10% JCT
     * @param int $amount
     * @return $this
     */
    public function setSubtotalExclJct10(int $amount)
    {
        return $this->setData(self::KEY_SUBTOTAL_EXCL_JCT_10, $amount);
    }

    /**
     * Set subtotal subject to 8% JCT
     * @param int $amount
     * @return $this
     */
    public function setSubtotalExclJct8(int $amount)
    {
        return $this->setData(self::KEY_SUBTOTAL_EXCL_JCT_8, $amount);
    }

    /**
     * Set subtotal subject to 10% JCT (Incl. Tax)
     * @param int $amount
     * @return $this
     */
    public function setSubtotalInclJct10(int $amount)
    {
        return $this->setData(self::KEY_SUBTOTAL_INCL_JCT_10, $amount);
    }

    /**
     * Set subtotal subject to 8% JCT (Incl. Tax)
     * @param int $amount
     * @return $this
     */
    public function setSubtotalInclJct8(int $amount)
    {
        return $this->setData(self::KEY_SUBTOTAL_INCL_JCT_8, $amount);
    }

    /**
     * Set 10% JCT amount
     * @param int $amount
     * @return $this
     */
    public function setJct10Amount(int $amount)
    {
        return $this->setData(self::KEY_JCT_10_AMOUNT, $amount);
    }

    /**
     * Set 8% JCT amount
     * @param int $amount
     * @return $this
     */
    public function setJct8Amount(int $amount)
    {
        return $this->setData(self::KEY_JCT_8_AMOUNT, $amount);
    }

    /**
     * Set isTaxIncluded
     * @param boolean $isTaxIncluded
     * @return $this
     */
    public function setIsTaxIncluded(bool $isTaxIncluded)
    {
        return $this->setData(self::KEY_IS_TAX_INCLUDED, $isTaxIncluded);
    }
}
