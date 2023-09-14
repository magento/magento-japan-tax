<?php

namespace Japan\Tax\Model\InvoiceTax;

use Magento\Framework\Model\AbstractExtensibleModel;
use Japan\Tax\Api\Data\InvoiceTaxBlockInterface;

/**
 * @codeCoverageIgnore
 */
class InvoiceTaxBlock extends AbstractExtensibleModel implements InvoiceTaxBlockInterface
{
    /**#@+
     * Constants defined for keys of array, makes typos less likely
     */
    const KEY_TAX_PERCENT          = 'tax_percent';
    const KEY_TOTAL                = 'total';
    const KEY_TOTAL_INCL_TAX       = 'total_incl_tax';
    const KEY_TAX                  = 'tax';
    const KEY_ITEMS                = 'items';
    const KEY_TAXABLE_AMOUNT       = 'taxable_amount';
    const KEY_DISCOUNT_AMOUNT      = 'discount_amount';
    const KEY_APPLIED_TAXES        = 'applied_taxes';
    const KEY_ASSOCIATED_ITEM_CODE = 'associated_item_code';
    const KEY_DISCOUNT_TAX_COMPENSATION_AMOUNT = 'discount_tax_compensation_amount';
    const KEY_IS_TAX_INCLUDED      = 'is_tax_included';
    /**#@-*/

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
    public function getTotal()
    {
        return $this->getData(self::KEY_TOTAL);
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalInclTax()
    {
        return $this->getData(self::KEY_TOTAL_INCL_TAX);
    }

    /**
     * {@inheritdoc}
     */
    public function getTax()
    {
        return $this->getData(self::KEY_TAX);
    }

    /**
     * {@inheritdoc}
     */
    public function getTaxableAmount()
    {
        return $this->getData(self::KEY_TAXABLE_AMOUNT);
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
    public function getDiscountTaxCompensationAmount()
    {
        return $this->getData(self::KEY_DISCOUNT_TAX_COMPENSATION_AMOUNT);
    }

    /**
     * {@inheritdoc}
     */
    public function getAppliedTaxes()
    {
        return $this->getData(self::KEY_APPLIED_TAXES);
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
    public function setTotal($total)
    {
        return $this->setData(self::KEY_TOTAL, $total);
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
     * Set row total
     *
     * @param float $rowTotal
     * @return $this
     */
    public function setRowTotal($rowTotal)
    {
        return $this->setData(self::KEY_TOTAL, $rowTotal);
    }

    /**
     * Set row total including tax
     *
     * @param float $rowTotalInclTax
     * @return $this
     */
    public function setTotalInclTax($rowTotalInclTax)
    {
        return $this->setData(self::KEY_TOTAL_INCL_TAX, $rowTotalInclTax);
    }

    /**
     * Set row tax amount
     *
     * @param float $rowTax
     * @return $this
     */
    public function setTax($rowTax)
    {
        return $this->setData(self::KEY_TAX, $rowTax);
    }

    /**
     * Set taxable amount
     *
     * @param float $taxableAmount
     * @return $this
     */
    public function setTaxableAmount($taxableAmount)
    {
        return $this->setData(self::KEY_TAXABLE_AMOUNT, $taxableAmount);
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
     * Set discount tax compensation amount
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
     * Set associated item code
     *
     * @param int $associatedItemCode
     * @return $this
     */
    public function setAssociatedItemCode($associatedItemCode)
    {
        return $this->setData(self::KEY_ASSOCIATED_ITEM_CODE, $associatedItemCode);
    }

   /**
     * Get items
     *
     * @return \Japan\Tax\Api\Data\InvoiceTaxItemInterface[] | null
     */
    public function getItems()
    {
        return $this->getData(self::KEY_ITEMS);
    }

    /**
     * Set items
     *
     * @param \Japan\Tax\Api\Data\InvoiceTaxItemInterface[] $appliedTaxes
     * @return $this
     */
    public function setItems(array $items = null)
    {
        return $this->setData(self::KEY_ITEMS, $items);
    }

    /**
     * Get isTaxIncluded
     *
     * @return boolean
     */
    public function getIsTaxIncluded()
    {
        return $this->getData(self::KEY_IS_TAX_INCLUDED);
    }

    /**
     * Set isTaxIncluded
     *
     * @param boolean $isTaxIncluded
     * @return $this
     */
    public function setIsTaxIncluded($isTaxIncluded)
    {
        return $this->setData(self::KEY_IS_TAX_INCLUDED, $isTaxIncluded);
    }
}