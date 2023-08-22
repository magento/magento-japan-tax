<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Japan\Tax\Test\Unit\Model;

use Japan\Tax\Model\TaxCalculation;
use Japan\Tax\Api\Data\InvoiceTaxInterfaceFactory;
use Japan\Tax\Api\Data\InvoiceTaxBlockInterfaceFactory;
use Japan\Tax\Api\Data\InvoiceTaxItemInterfaceFactory;
use Japan\Tax\Model\InvoiceTax\InvoiceTax;
use Japan\Tax\Model\InvoiceTax\InvoiceTaxBlock;
use Japan\Tax\Model\InvoiceTax\InvoiceTaxItem;
use Japan\Tax\Model\CurrencyRoundingFactory;
use Japan\Tax\Model\CurrencyRounding;
use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Tax\Api\Data\QuoteDetailsInterface;
use Magento\Tax\Model\Calculation;
use Magento\Tax\Model\Config;
use Magento\Tax\Model\TaxDetails\AppliedTax;
use Magento\Tax\Model\TaxDetails\AppliedTaxRate;
use Magento\Tax\Api\TaxClassManagementInterface;
use Magento\Tax\Api\Data\QuoteDetailsItemInterface;
use Magento\Tax\Api\Data\AppliedTaxInterfaceFactory;
use Magento\Tax\Api\Data\AppliedTaxRateInterfaceFactory;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Api\Data\ShippingInterface;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Quote\Model\Quote\Item\Option;
use Magento\Quote\Model\Quote\Item\OptionFactory;
use Magento\Quote\Model\Quote\ItemFactory;
use Magento\Quote\Model\Quote\Shipping;
use Magento\Quote\Model\Quote\ShippingAssignment;
use Magento\Quote\Model\Quote\TotalsCollector;
use Magento\Quote\Model\Quote\TotalsCollectorFactory;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class TaxCalculationTest extends TestCase
{
    const TAX_CLASS_KEY_10 = '10';
    const TAX_CLASS_KEY_8  = '8';

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var TaxCalculation
     */
    protected $taxCalculation;

    /**
     * @var StoreManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $storeManagerMock;

    /**
     * @var TaxClassManagementInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $taxClassManagementMock;

    /**
     * @var AppliedTaxInterfaceFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $appliedTaxDataObjectFactoryMock;

    /**
     * @var AppliedTaxRateInterfaceFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $appliedTaxRateDataObjectFactoryMock;

    /**
     * @var InvoiceTaxInterfaceFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $invoiceTaxFactoryMock;

    /**
     * @var InvoiceTaxBlockInterfaceFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $invoiceTaxBlockFactoryMock;

    /**
     * @var InvoiceTaxItemInterfaceFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $invoiceTaxItemFactoryMock;

    /**
     * @var CurrencyRoundingFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $currencyRoundingFactoryMock;

    /**
     * @var Calculation|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $calculationMock;

    /**
     * @var Config|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $taxConfigMock;

    /**
     * @var Store|MockObject
     */
    private $storeMock;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->storeMock = $this->getMockBuilder(Store::class)
            ->setMethods(['getBaseCurrencyCode', 'getCurrentCurrencyCode'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeMock->method('getBaseCurrencyCode')
            ->willReturn('JPY');
        $this->storeMock->method('getCurrentCurrencyCode')
            ->willReturn('JPY');
        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->setMethods(['getStore'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManagerMock->method('getStore')
            ->willReturn($this->storeMock);

        $this->taxClassManagementMock = $this->mockTaxClassManagement();

        // Use the mockFactory method
        $this->appliedTaxDataObjectFactoryMock = $this->mockFactory(
            AppliedTaxInterfaceFactory::class,
            AppliedTax::class
        );

        $this->appliedTaxRateDataObjectFactoryMock = $this->mockFactory(
            AppliedTaxRateInterfaceFactory::class,
            AppliedTaxRate::class
        );

        $this->invoiceTaxFactoryMock = $this->mockFactory(
            InvoiceTaxInterfaceFactory::class,
            InvoiceTax::class
        );

        $this->invoiceTaxBlockFactoryMock = $this->mockFactory(
            InvoiceTaxBlockInterfaceFactory::class,
            InvoiceTaxBlock::class
        );

        $this->invoiceTaxItemFactoryMock = $this->mockFactory(
            InvoiceTaxItemInterfaceFactory::class,
            InvoiceTaxItem::class
        );

        $this->calculationMock = $this->mockCreateCalculationTool();

        $this->taxConfigMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->currencyRoundingFactoryMock = $this->mockFactory(
            CurrencyRoundingFactory::class,
            CurrencyRounding::class
        );

        $this->taxCalculation = $this->objectManager->getObject(
            TaxCalculation::class,
            [
                'storeManager' => $this->storeManagerMock,
                'taxClassManagement' => $this->taxClassManagementMock,
                'appliedTaxDataObjectFactory' => $this->appliedTaxDataObjectFactoryMock,
                'appliedTaxRateDataObjectFactory' => $this->appliedTaxRateDataObjectFactoryMock,
                'invoiceTaxFactory' => $this->invoiceTaxFactoryMock,
                'invoiceTaxBlockFactory' => $this->invoiceTaxBlockFactoryMock,
                'invoiceTaxItemFactory' => $this->invoiceTaxItemFactoryMock,
                'currencyRoundingFactory' => $this->currencyRoundingFactoryMock,
                'calculation' => $this->calculationMock,
                'taxConfig' => $this->taxConfigMock,
            ]
        );
    }

    /** ======================Caclulate Tax Not In Price====================== **/

    /**
     * Test the calculateTax method for correctness when calculating a 10 percent tax not included in price
     */
    public function testCalculateNotInPriceTax10Percent()
    {
        $quoteDetailsMock = $this->getMockForAbstractClass(QuoteDetailsInterface::class);
        $quoteDetailsMock->method('getItems')
            ->willReturn($this->mockCreateQuoteDetailsItems([
                [
                    'code' => 'item1',
                    'type' => 'product',
                    'tax_class_key' => self::TAX_CLASS_KEY_10,
                    'unit_price' => 100,
                    'quantity' => 1,
                    'is_tax_included' => false,
                    'short_description' => 'Item 1',
                    'discount_amount' => 0.0,
                    'tax_class_id' => 1
                ],
                [
                    'code' => 'item2',
                    'type' => 'product',
                    'tax_class_key' => self::TAX_CLASS_KEY_10,
                    'unit_price' => 100,
                    'quantity' => 2,
                    'is_tax_included' => false,
                    'short_description' => 'Item 2',
                    'discount_amount' => 0.0,
                    'tax_class_id' => 1
                ]
            ]));

        $res = $this->taxCalculation->calculateTax($quoteDetailsMock, false, 1, true);
        foreach ($res->getBlocks() as $block) {
            $this->assertEquals(10, $block->getTaxPercent());
            $this->assertEquals(300, $block->getTotal());
            $this->assertEquals(330, $block->getTotalInclTax());
            $this->assertEquals(30, $block->getTax());
        }
    }

    /**
     * Test the calculateTax method for correctness when calculating an 8 percent tax not included in price
     */
    public function testCalculateNotInPriceTax8Percent()
    {
        $quoteDetailsMock = $this->getMockForAbstractClass(QuoteDetailsInterface::class);
        $quoteDetailsMock->method('getItems')
            ->willReturn($this->mockCreateQuoteDetailsItems([
                [
                    'code' => 'item1',
                    'type' => 'product',
                    'tax_class_key' => self::TAX_CLASS_KEY_8,
                    'unit_price' => 100,
                    'quantity' => 1,
                    'is_tax_included' => false,
                    'short_description' => 'Item 1',
                    'discount_amount' => 0.0,
                    'tax_class_id' => 1
                ],
                [
                    'code' => 'item2',
                    'type' => 'product',
                    'tax_class_key' => self::TAX_CLASS_KEY_8,
                    'unit_price' => 100,
                    'quantity' => 2,
                    'is_tax_included' => false,
                    'short_description' => 'Item 2',
                    'discount_amount' => 0.0,
                    'tax_class_id' => 1
                ]
            ]));

        $res = $this->taxCalculation->calculateTax($quoteDetailsMock, false, 1, true);
        foreach ($res->getBlocks() as $block) {
            $this->assertEquals(8, $block->getTaxPercent());
            $this->assertEquals(300, $block->getTotal());
            $this->assertEquals(324, $block->getTotalInclTax());
            $this->assertEquals(24, $block->getTax());
        }
    }

    /**
     * Test the calculateTax method for correctness when calculating both 10 and 8 percent taxes not included in price
     */
    public function testCalculateNotInPriceTax10And8Percent()
    {
        $quoteDetailsMock = $this->getMockForAbstractClass(QuoteDetailsInterface::class);
        $quoteDetailsMock->method('getItems')
            ->willReturn($this->mockCreateQuoteDetailsItems([
                [
                    'code' => 'item1',
                    'type' => 'product',
                    'tax_class_key' => self::TAX_CLASS_KEY_10,
                    'unit_price' => 100,
                    'quantity' => 1,
                    'is_tax_included' => false,
                    'short_description' => 'false 1',
                    'discount_amount' => 0.0,
                    'tax_class_id' => 1
                ],
                [
                    'code' => 'item2',
                    'type' => 'product',
                    'tax_class_key' => self::TAX_CLASS_KEY_8,
                    'unit_price' => 100,
                    'quantity' => 2,
                    'is_tax_included' => false,
                    'short_description' => 'Item 2',
                    'discount_amount' => 0.0,
                    'tax_class_id' => 1
                ]
            ]));

        $res = $this->taxCalculation->calculateTax($quoteDetailsMock, false, 1, true);
        $blocks = $res->getBlocks();

        $this->assertEquals(10, $blocks[0]->getTaxPercent());
        $this->assertEquals(100, $blocks[0]->getTotal());
        $this->assertEquals(110, $blocks[0]->getTotalInclTax());
        $this->assertEquals(10, $blocks[0]->getTax());

        $this->assertEquals(8, $blocks[1]->getTaxPercent());
        $this->assertEquals(200, $blocks[1]->getTotal());
        $this->assertEquals(216, $blocks[1]->getTotalInclTax());
        $this->assertEquals(16, $blocks[1]->getTax());
    }

    /**
     * Test the calculateTax method for correctness when calculating both 10 and 8 percent taxes not included in price,
     * while considering shipping costs
     */
    public function testCalculateNotInPriceTax10And8PercentWithShipping()
    {
        $quoteDetailsMock = $this->getMockForAbstractClass(QuoteDetailsInterface::class);
        $quoteDetailsMock->method('getItems')
            ->willReturn($this->mockCreateQuoteDetailsItems([
                [
                    'code' => 'item1',
                    'type' => 'product',
                    'tax_class_key' => self::TAX_CLASS_KEY_10,
                    'unit_price' => 100,
                    'quantity' => 1,
                    'is_tax_included' => false,
                    'short_description' => 'Item 1',
                    'discount_amount' => 0.0,
                    'tax_class_id' => 1
                ],
                [
                    'code' => 'item2',
                    'type' => 'product',
                    'tax_class_key' => self::TAX_CLASS_KEY_8,
                    'unit_price' => 100,
                    'quantity' => 2,
                    'is_tax_included' => false,
                    'short_description' => 'Item 2',
                    'discount_amount' => 0.0,
                    'tax_class_id' => 1
                ],
                [
                    'code' => 'shipping',
                    'type' => 'shipping',
                    'tax_class_key' => self::TAX_CLASS_KEY_10,
                    'unit_price' => 10,
                    'quantity' => 1,
                    'is_tax_included' => false,
                    'short_description' => 'Item 1',
                    'discount_amount' => 0.0,
                    'tax_class_id' => 1
                ],
            ]));

        $res = $this->taxCalculation->calculateTax($quoteDetailsMock, false, 1, true);
        $blocks = $res->getBlocks();

        $this->assertEquals(10, $blocks[0]->getTaxPercent());
        $this->assertEquals(110, $blocks[0]->getTotal());
        $this->assertEquals(121, $blocks[0]->getTotalInclTax());
        $this->assertEquals(11, $blocks[0]->getTax());

        $this->assertEquals(8, $blocks[1]->getTaxPercent());
        $this->assertEquals(200, $blocks[1]->getTotal());
        $this->assertEquals(216, $blocks[1]->getTotalInclTax());
        $this->assertEquals(16, $blocks[1]->getTax());
    }

    /**
     * Test the calculateTax method for correctness when calculating both 10 and 8 percent taxes not included in price,
     * while considering discounts
     */
    public function testCalculateNotInPriceTax10And8PercentWithDiscount()
    {
        $quoteDetailsMock = $this->getMockForAbstractClass(QuoteDetailsInterface::class);
        $quoteDetailsMock->method('getItems')
            ->willReturn($this->mockCreateQuoteDetailsItems([
                [
                    'code' => 'item1',
                    'type' => 'product',
                    'tax_class_key' => self::TAX_CLASS_KEY_10,
                    'unit_price' => 100,
                    'quantity' => 1,
                    'is_tax_included' => false,
                    'short_description' => 'Item 1',
                    'discount_amount' => 50,
                    'tax_class_id' => 1
                ],
                [
                    'code' => 'item2',
                    'type' => 'product',
                    'tax_class_key' => self::TAX_CLASS_KEY_8,
                    'unit_price' => 100,
                    'quantity' => 2,
                    'is_tax_included' => false,
                    'short_description' => 'Item 2',
                    'discount_amount' => 0.0,
                    'tax_class_id' => 1
                ]
            ]));

        $res = $this->taxCalculation->calculateTax($quoteDetailsMock, false, 1, true);
        $blocks = $res->getBlocks();

        $this->assertEquals(10, $blocks[0]->getTaxPercent());
        $this->assertEquals(100, $blocks[0]->getTotal());
        $this->assertEquals(105, $blocks[0]->getTotalInclTax());
        $this->assertEquals(5, $blocks[0]->getTax());

        $this->assertEquals(8, $blocks[1]->getTaxPercent());
        $this->assertEquals(200, $blocks[1]->getTotal());
        $this->assertEquals(216, $blocks[1]->getTotalInclTax());
        $this->assertEquals(16, $blocks[1]->getTax());
    }

    /** ======================Caclulate Tax In Price====================== **/

    /**
     * Test the calculateTax method for correctness when calculating a 10 percent tax included in price
     */
    public function testCalculateInPriceTax10Percent()
    {
        $quoteDetailsMock = $this->getMockForAbstractClass(QuoteDetailsInterface::class);
        $quoteDetailsMock->method('getItems')
            ->willReturn($this->mockCreateQuoteDetailsItems([
                [
                    'code' => 'item1',
                    'type' => 'product',
                    'tax_class_key' => self::TAX_CLASS_KEY_10,
                    'unit_price' => 100,
                    'quantity' => 1,
                    'is_tax_included' => true,
                    'short_description' => 'Item 1',
                    'discount_amount' => 0.0,
                    'tax_class_id' => 1
                ],
                [
                    'code' => 'item2',
                    'type' => 'product',
                    'tax_class_key' => self::TAX_CLASS_KEY_10,
                    'unit_price' => 100,
                    'quantity' => 2,
                    'is_tax_included' => true,
                    'short_description' => 'Item 2',
                    'discount_amount' => 0.0,
                    'tax_class_id' => 1
                ]
            ]));

        $res = $this->taxCalculation->calculateTax($quoteDetailsMock, false, 1, true);
        foreach ($res->getBlocks() as $block) {
            $this->assertEquals(10, $block->getTaxPercent());
            $this->assertEquals(273, $block->getTotal());
            $this->assertEquals(300, $block->getTotalInclTax());
            $this->assertEquals(27, $block->getTax());
        }
    }

    /**
     * Test the calculateTax method for correctness when calculating an 8 percent tax included in price
     */
    public function testCalculateInPriceTax8Percent()
    {
        $quoteDetailsMock = $this->getMockForAbstractClass(QuoteDetailsInterface::class);
        $quoteDetailsMock->method('getItems')
            ->willReturn($this->mockCreateQuoteDetailsItems([
                [
                    'code' => 'item1',
                    'type' => 'product',
                    'tax_class_key' => self::TAX_CLASS_KEY_8,
                    'unit_price' => 100,
                    'quantity' => 1,
                    'is_tax_included' => true,
                    'short_description' => 'Item 1',
                    'discount_amount' => 0.0,
                    'tax_class_id' => 1
                ],
                [
                    'code' => 'item2',
                    'type' => 'product',
                    'tax_class_key' => self::TAX_CLASS_KEY_8,
                    'unit_price' => 100,
                    'quantity' => 2,
                    'is_tax_included' => true,
                    'short_description' => 'Item 2',
                    'discount_amount' => 0.0,
                    'tax_class_id' => 1
                ]
            ]));

        $res = $this->taxCalculation->calculateTax($quoteDetailsMock, false, 1, true);
        foreach ($res->getBlocks() as $block) {
            $this->assertEquals(8, $block->getTaxPercent());
            $this->assertEquals(278, $block->getTotal());
            $this->assertEquals(300, $block->getTotalInclTax());
            $this->assertEquals(22, $block->getTax());
        }
    }

    /**
     * Test the calculateTax method for correctness when calculating both 10 and 8 percent taxes included in price
     */
    public function testCalculateInPriceTax10And8Percent()
    {
        $quoteDetailsMock = $this->getMockForAbstractClass(QuoteDetailsInterface::class);
        $quoteDetailsMock->method('getItems')
            ->willReturn($this->mockCreateQuoteDetailsItems([
                [
                    'code' => 'item1',
                    'type' => 'product',
                    'tax_class_key' => self::TAX_CLASS_KEY_10,
                    'unit_price' => 100,
                    'quantity' => 1,
                    'is_tax_included' => true,
                    'short_description' => 'Item 1',
                    'discount_amount' => 0.0,
                    'tax_class_id' => 1
                ],
                [
                    'code' => 'item2',
                    'type' => 'product',
                    'tax_class_key' => self::TAX_CLASS_KEY_8,
                    'unit_price' => 100,
                    'quantity' => 2,
                    'is_tax_included' => true,
                    'short_description' => 'Item 2',
                    'discount_amount' => 0.0,
                    'tax_class_id' => 1
                ]
            ]));

        $res = $this->taxCalculation->calculateTax($quoteDetailsMock, false, 1, true);
        $blocks = $res->getBlocks();

        $this->assertEquals(10, $blocks[0]->getTaxPercent());
        $this->assertEquals(91, $blocks[0]->getTotal());
        $this->assertEquals(100, $blocks[0]->getTotalInclTax());
        $this->assertEquals(9, $blocks[0]->getTax());

        $this->assertEquals(8, $blocks[1]->getTaxPercent());
        $this->assertEquals(186, $blocks[1]->getTotal());
        $this->assertEquals(200, $blocks[1]->getTotalInclTax());
        $this->assertEquals(14, $blocks[1]->getTax());
    }

    /**
     * Test the calculateTax method for correctness when calculating both 10 and 8 percent taxes included in price,
     * while considering shipping costs
     */
    public function testCalculateInPriceTax10And8PercentWithShipping()
    {
        $quoteDetailsMock = $this->getMockForAbstractClass(QuoteDetailsInterface::class);
        $quoteDetailsMock->method('getItems')
            ->willReturn($this->mockCreateQuoteDetailsItems([
                [
                    'code' => 'item1',
                    'type' => 'product',
                    'tax_class_key' => self::TAX_CLASS_KEY_10,
                    'unit_price' => 100,
                    'quantity' => 1,
                    'is_tax_included' => true,
                    'short_description' => 'Item 1',
                    'discount_amount' => 0.0,
                    'tax_class_id' => 1
                ],
                [
                    'code' => 'item2',
                    'type' => 'product',
                    'tax_class_key' => self::TAX_CLASS_KEY_8,
                    'unit_price' => 100,
                    'quantity' => 2,
                    'is_tax_included' => true,
                    'short_description' => 'Item 2',
                    'discount_amount' => 0.0,
                    'tax_class_id' => 1
                ],
                [
                    'code' => 'shipping',
                    'type' => 'shipping',
                    'tax_class_key' => self::TAX_CLASS_KEY_10,
                    'unit_price' => 10,
                    'quantity' => 1,
                    'is_tax_included' => true,
                    'short_description' => 'Item 1',
                    'discount_amount' => 0.0,
                    'tax_class_id' => 1
                ],
            ]));

        $res = $this->taxCalculation->calculateTax($quoteDetailsMock, false, 1, true);
        $blocks = $res->getBlocks();

        $this->assertEquals(10, $blocks[0]->getTaxPercent());
        $this->assertEquals(100, $blocks[0]->getTotal());
        $this->assertEquals(110, $blocks[0]->getTotalInclTax());
        $this->assertEquals(10, $blocks[0]->getTax());

        $this->assertEquals(8, $blocks[1]->getTaxPercent());
        $this->assertEquals(186, $blocks[1]->getTotal());
        $this->assertEquals(200, $blocks[1]->getTotalInclTax());
        $this->assertEquals(14, $blocks[1]->getTax());
    }

    /**
     * Test the calculateTax method for correctness when calculating both 10 and 8 percent taxes included in price,
     * while considering discounts
     */
    public function testCalculateInPriceTax10And8PercentWithDiscount()
    {
        $quoteDetailsMock = $this->getMockForAbstractClass(QuoteDetailsInterface::class);
        $quoteDetailsMock->method('getItems')
            ->willReturn($this->mockCreateQuoteDetailsItems([
                [
                    'code' => 'item1',
                    'type' => 'product',
                    'tax_class_key' => self::TAX_CLASS_KEY_10,
                    'unit_price' => 100,
                    'quantity' => 1,
                    'is_tax_included' => true,
                    'short_description' => 'Item 1',
                    'discount_amount' => 50,
                    'tax_class_id' => 1
                ],
                [
                    'code' => 'item2',
                    'type' => 'product',
                    'tax_class_key' => self::TAX_CLASS_KEY_8,
                    'unit_price' => 100,
                    'quantity' => 2,
                    'is_tax_included' => true,
                    'short_description' => 'Item 2',
                    'discount_amount' => 0.0,
                    'tax_class_id' => 1
                ]
            ]));

        $res = $this->taxCalculation->calculateTax($quoteDetailsMock, false, 1, true);
        $blocks = $res->getBlocks();

        $this->assertEquals(10, $blocks[0]->getTaxPercent());
        $this->assertEquals(96, $blocks[0]->getTotal());
        $this->assertEquals(100, $blocks[0]->getTotalInclTax());
        $this->assertEquals(4, $blocks[0]->getTax());

        $this->assertEquals(8, $blocks[1]->getTaxPercent());
        $this->assertEquals(186, $blocks[1]->getTotal());
        $this->assertEquals(200, $blocks[1]->getTotalInclTax());
        $this->assertEquals(14, $blocks[1]->getTax());
    }

    private function mockFactory(string $factoryName, string $instanceType)
    {
        $mock = $this->createPartialMock($factoryName, ['create']);
        $mock
            ->method('create')
            ->willReturnCallback(fn() => $this->objectManager->getObject($instanceType));

        return $mock;
    }

    private function mockCreateQuoteDetailsItems(array $data = [])
    {
        $quoteDetailsItems = [];
        foreach ($data as $itemData) {
            $item = $this->getMockForAbstractClass(QuoteDetailsItemInterface::class);

            $item->method('getCode')->willReturn($itemData['code']);
            $item->method('getType')->willReturn($itemData['type']);
            $item->method('getTaxClassKey')->willReturn($itemData['tax_class_key']);
            $item->method('getUnitPrice')->willReturn($itemData['unit_price']);
            $item->method('getQuantity')->willReturn($itemData['quantity']);
            $item->method('getIsTaxIncluded')->willReturn($itemData['is_tax_included']);
            $item->method('getShortDescription')->willReturn($itemData['short_description']);
            $item->method('getDiscountAmount')->willReturn($itemData['discount_amount']);
            $item->method('getTaxClassId')->willReturn($itemData['tax_class_id']);

            $quoteDetailsItems[] = $item;
        }

        return $quoteDetailsItems;
    }

    private function mockTaxClassManagement()
    {
        $taxClassManagementMock = $this->getMockBuilder(TaxClassManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $taxClassManagementMock
            ->method('getTaxClassId')
            ->willReturnCallback(function ($classKey, $id) {
                return $classKey;
            });

        return $taxClassManagementMock;
    }

    private function mockCreateCalculationTool()
    {
        $calculationToolMock = $this->getMockBuilder(Calculation::class)
            ->disableOriginalConstructor()
            ->getMock();

        // FIXME: fix calculationToolMock->calcTaxAmount
        $calculationToolMock
            ->method('calcTaxAmount')
            ->willReturnCallback(function ($price, $taxRate, $priceIncludeTax, $round = true) {
                $taxRate = $taxRate / 100.0;
                if ($priceIncludeTax) {
                    $amount = $price * (1 - 1 / (1 + $taxRate));
                } else {
                    $amount = $price * $taxRate;
                }
                return $amount;
            });

        $calculationToolMock
            ->method('getRateRequest')
            ->willReturnCallback(function ($shippingAddress, $billingAddress, $customerTaxClassId, $storeId, $customerId) {
                return $this->objectManager->getObject(DataObject::class);
            });

        $calculationToolMock
            ->method('getRate')
            ->willReturnCallback(function ($addressRequestObject) {
                return self::TAX_CLASS_KEY_8 == $addressRequestObject->getProductClassId() ? 8 : 10;
            });

        $calculationToolMock
            ->method('getStoreRate')
            ->willReturnCallback(function ($addressRequestObject) {
                return self::TAX_CLASS_KEY_8 == $addressRequestObject->getProductClassId() ? 8 : 10;
            });

        $calculationToolMock
            ->method('getAppliedRates')
            ->willReturnCallback(function ($addressRequestObject) {
                $taxPercent = self::TAX_CLASS_KEY_8 == $addressRequestObject->getProductClassId() ? 8 : 10;
                return [[
                    'id' => 1,
                    'percent' => $taxPercent,
                    'rates' => [[
                        'code' => 'test_' . $taxPercent,
                        'percent' => $taxPercent,
                        'title' => 'test_' . $taxPercent
                    ]]
                ]];
            });

        return $calculationToolMock;
    }
}
