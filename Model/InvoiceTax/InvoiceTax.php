<?php
/**
 * This file is part of the Japanese Consumption Tax Extension For Magento2 project.
 *
 * Copyright (c) 2023 Adobe (or other copyright holders)
 *
 * For the full copyright and license information, please view the OSL-3.0
 * license that is bundled with this source code in the file LICENSE, or
 * at https://opensource.org/licenses/OSL-3.0
 */
namespace Magentoj\JapaneseConsumptionTax\Model\InvoiceTax;

use Magento\Framework\Model\AbstractExtensibleModel;
use Magentoj\JapaneseConsumptionTax\Api\Data\InvoiceTaxInterface;

/**
 * @codeCoverageIgnore
 */
class InvoiceTax extends AbstractExtensibleModel implements InvoiceTaxInterface
{
    /**#@+
     * Constants defined for keys of array, makes typos less likely
     */
    public const KEY_SUBTOTAL      = 'subtotal';
    public const KEY_TAX_AMOUNT    = 'tax_amount';
    public const KEY_APPLIED_TAXES = 'applied_taxes';
    public const KEY_BLOCKS         = 'blocks';
    public const KEY_DISCOUNT_TAX_COMPENSATION_AMOUNT = 'discount_tax_compensation_amount';
    /**#@-*/

    /**
     * @inheritdoc
     */
    public function getSubtotal()
    {
        return $this->getData(self::KEY_SUBTOTAL);
    }

    /**
     * @inheritdoc
     */
    public function getTaxAmount()
    {
        return $this->getData(self::KEY_TAX_AMOUNT);
    }

    /**
     * @inheritdoc
     */
    public function getDiscountTaxCompensationAmount()
    {
        return $this->getData(self::KEY_DISCOUNT_TAX_COMPENSATION_AMOUNT);
    }

    /**
     * @inheritdoc
     */
    public function getAppliedTaxes()
    {
        return $this->getData(self::KEY_APPLIED_TAXES);
    }

    /**
     * @inheritdoc
     */
    public function getBlocks()
    {
        return $this->getData(self::KEY_BLOCKS);
    }

    /**
     * Set subtotal
     *
     * @param float $subtotal
     * @return $this
     */
    public function setSubtotal($subtotal)
    {
        return $this->setData(self::KEY_SUBTOTAL, $subtotal);
    }

    /**
     * Set tax amount
     *
     * @param float $taxAmount
     * @return $this
     */
    public function setTaxAmount($taxAmount)
    {
        return $this->setData(self::KEY_TAX_AMOUNT, $taxAmount);
    }

    /**
     * Set discount amount
     *
     * @param float $discountTaxCompensationAmount
     * @return $this
     */
    public function setDiscountTaxCompensationAmount($discountTaxCompensationAmount)
    {
        return $this->setData(
            self::KEY_DISCOUNT_TAX_COMPENSATION_AMOUNT,
            $discountTaxCompensationAmount
        );
    }

    /**
     * Set applied taxes
     *
     * @param \Magento\Tax\Api\Data\AppliedTaxInterface[] $appliedTaxes
     * @return $this
     */
    public function setAppliedTaxes(array $appliedTaxes = null)
    {
        return $this->setData(self::KEY_APPLIED_TAXES, $appliedTaxes);
    }

    /**
     * Set blocks
     *
     * @param \Magentoj\JapaneseConsumptionTax\Api\Data\InvoiceTaxBlockInterface[] $blocks
     * @return $this
     */
    public function setBlocks(array $blocks = null)
    {
        return $this->setData(self::KEY_BLOCKS, $blocks);
    }

    /**
     * @inheritdoc
     *
     * @return \Magento\Tax\Api\Data\TaxDetailsExtensionInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * @inheritdoc
     *
     * @param \Magento\Tax\Api\Data\TaxDetailsExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(\Magento\Tax\Api\Data\TaxDetailsExtensionInterface $extensionAttributes)
    {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
