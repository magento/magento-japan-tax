<?php
/**
 * This file is part of Japanese Consumption Tax Extension For Magento2 the project.
 *
 * Copyright (c) 2023 Adobe (or other copyright holders)
 *
 * For the full copyright and license information, please view the OSL-3.0
 * license that is bundled with this source code in the file LICENSE, or
 * at https://opensource.org/licenses/OSL-3.0
 */
declare(strict_types=1);

namespace Magentoj\JapaneseConsumptionTax\Test\Unit\Model\Calculation;

use Magentoj\JapaneseConsumptionTax\Api\Data\InvoiceTaxInterfaceFactory;
use Magentoj\JapaneseConsumptionTax\Api\Data\InvoiceTaxBlockInterfaceFactory;
use Magentoj\JapaneseConsumptionTax\Api\Data\InvoiceTaxItemInterfaceFactory;
use Magentoj\JapaneseConsumptionTax\Model\InvoiceTax\InvoiceTax;
use Magentoj\JapaneseConsumptionTax\Model\InvoiceTax\InvoiceTaxBlock;
use Magentoj\JapaneseConsumptionTax\Model\InvoiceTax\InvoiceTaxItem;
use Magentoj\JapaneseConsumptionTax\Model\Calculation\JctTaxCalculator;
use Magentoj\JapaneseConsumptionTax\Model\CurrencyRoundingFactory;
use Magentoj\JapaneseConsumptionTax\Model\CurrencyRounding;
use Magentoj\JapaneseConsumptionTax\Model\Calculation\OrderItemAdapter;
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

class JctTaxCalculatorTest extends TestCase
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
     * @var AppliedTaxInterfaceFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $appliedTaxDataObjectFactoryMock;

    /**
     * @var AppliedTaxRateInterfaceFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $appliedTaxRateDataObjectFactoryMock;

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
     * @var JctTaxCalculator
     */
    protected $jctTaxCalculator;

    protected $appliedRates10 = [[
        'id' => 1,
        'percent' => 10.0,
        'rates' => [[
            'code' => 'japantax10',
            'percent' => 10.0,
            'title' => '10%'
        ]]
    ]];

    protected $appliedRates8 = [[
        'id' => 1,
        'percent' => 8.0,
        'rates' => [[
            'code' => 'japantax8',
            'percent' => 8.0,
            'title' => '8%'
        ]]
    ]];

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        // Use the mockFactory method
        $this->appliedTaxDataObjectFactoryMock = $this->mockFactory(
            AppliedTaxInterfaceFactory::class,
            AppliedTax::class
        );

        $this->appliedTaxRateDataObjectFactoryMock = $this->mockFactory(
            AppliedTaxRateInterfaceFactory::class,
            AppliedTaxRate::class
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

        $this->jctTaxCalculator = $this->objectManager->getObject(
            JctTaxCalculator::class,
            [
                'appliedTaxDataObjectFactory' => $this->appliedTaxDataObjectFactoryMock,
                'appliedTaxRateDataObjectFactory' => $this->appliedTaxRateDataObjectFactoryMock,
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
     * Test the method for correctness when calculating 10 percent tax not included in price
     */
    public function testCalculateNotInPriceTax10Percent()
    {
        $items = $this->mockCreateQuoteDetailsItems([
                [
                    'unit_price' => 100,
                    'quantity' => 1,
                    'discount_amount' => 0.0,
                ],
                [
                    'unit_price' => 100,
                    'quantity' => 2,
                    'discount_amount' => 0.0,
                ]
        ]);

        $block = $this->jctTaxCalculator->calculateWithTaxNotInPrice(
            $items,
            10,
            $this->appliedRates10,
            'JPY'
        );
        $this->assertEquals(10, $block->getTaxPercent());
        $this->assertEquals(300, $block->getTotal());
        $this->assertEquals(330, $block->getTotalInclTax());
        $this->assertEquals(30, $block->getTax());
    }

    /**
     * Test the method for correctness when calculating 8 percent tax not included in price
     */
    public function testCalculateNotInPriceTax8Percent()
    {
        $items = $this->mockCreateQuoteDetailsItems([
            [
                'unit_price' => 100,
                'quantity' => 1,
                'discount_amount' => 0.0,
            ],
            [
                'unit_price' => 100,
                'quantity' => 2,
                'discount_amount' => 0.0,
            ]
        ]);

        $block = $this->jctTaxCalculator->calculateWithTaxNotInPrice(
            $items,
            8,
            $this->appliedRates8,
            'JPY'
        );
        $this->assertEquals(8, $block->getTaxPercent());
        $this->assertEquals(300, $block->getTotal());
        $this->assertEquals(324, $block->getTotalInclTax());
        $this->assertEquals(24, $block->getTax());
    }

    /**
     * Test the method for correctness when calculating 10 tax not included in price,
     * while considering discounts
     */
    public function testCalculateNotInPriceTax10WithDiscount()
    {
        $items = $this->mockCreateQuoteDetailsItems([
            [
                'unit_price' => 100,
                'quantity' => 1,
                'discount_amount' => 50.0,
            ],
        ]);

        $block = $this->jctTaxCalculator->calculateWithTaxNotInPrice(
            $items,
            10,
            $this->appliedRates10,
            'JPY'
        );

        $this->assertEquals(10, $block->getTaxPercent());
        $this->assertEquals(100, $block->getTotal());
        $this->assertEquals(110, $block->getTotalInclTax());
        $this->assertEquals(5, $block->getTax());
    }


    /**
     * Test the method for correctness when calculating 8 tax not included in price,
     * while considering discounts
     */
    public function testCalculateNotInPriceTax8WithDiscount()
    {
        $items = $this->mockCreateQuoteDetailsItems([
            [
                'unit_price' => 100,
                'quantity' => 1,
                'discount_amount' => 50,
            ],
        ]);

        $block = $this->jctTaxCalculator->calculateWithTaxNotInPrice(
            $items,
            8,
            $this->appliedRates8,
            'JPY'
        );

        $this->assertEquals(8, $block->getTaxPercent());
        $this->assertEquals(100, $block->getTotal());
        $this->assertEquals(108, $block->getTotalInclTax());
        $this->assertEquals(4, $block->getTax());
    }

    /** ======================Caclulate Tax In Price====================== **/

    /**
     * Test the method for correctness when calculating 10 percent tax included in price
     */
    public function testCalculateInPriceTax10Percent()
    {
        $items = $this->mockCreateQuoteDetailsItems([
            [
                'unit_price' => 100,
                'quantity' => 1,
                'discount_amount' => 0.0,
            ],
            [
                'unit_price' => 100,
                'quantity' => 2,
                'discount_amount' => 0.0,
            ]
        ]);

        $block = $this->jctTaxCalculator->calculateWithTaxInPrice(
            $items,
            10,
            $this->appliedRates10,
            'JPY'
        );
        $this->assertEquals(10, $block->getTaxPercent());
        $this->assertEquals(273, $block->getTotal());
        $this->assertEquals(300, $block->getTotalInclTax());
        $this->assertEquals(27, $block->getTax());
    }

    /**
     * Test the method for correctness when calculating 8 percent tax included in price
     */
    public function testCalculateInPriceTax8Percent()
    {
        $items = $this->mockCreateQuoteDetailsItems([
            [
                'unit_price' => 100,
                'quantity' => 1,
                'discount_amount' => 0.0,
            ],
            [
                'unit_price' => 100,
                'quantity' => 2,
                'discount_amount' => 0.0,
            ]
        ]);

        $block = $this->jctTaxCalculator->calculateWithTaxInPrice(
            $items,
            8,
            $this->appliedRates8,
            'JPY'
        );

        $this->assertEquals(8, $block->getTaxPercent());
        $this->assertEquals(278, $block->getTotal());
        $this->assertEquals(300, $block->getTotalInclTax());
        $this->assertEquals(22, $block->getTax());
    }

    /**
     * Test the method for correctness when calculating 8 percent taxes included in price,
     * while considering discounts
     */
    public function testCalculateInPriceTax8PercentWithDiscount()
    {
        $items = $this->mockCreateQuoteDetailsItems([
            [
                'unit_price' => 100,
                'quantity' => 1,
                'discount_amount' => 50,
            ],
        ]);

        $block = $this->jctTaxCalculator->calculateWithTaxInPrice(
            $items,
            8,
            $this->appliedRates8,
            'JPY'
        );

        $this->assertEquals(8, $block->getTaxPercent());
        $this->assertEquals(93, $block->getTotal());
        $this->assertEquals(100, $block->getTotalInclTax());
        $this->assertEquals(3, $block->getTax());
    }

    /**
     * Test the method for correctness when calculating 10 percent taxes included in price with order item adapter
     */
    public function testOrderItemAdapter()
    {
        $items = $this->mockCreateOrderItemAdapters([
                [
                    'unit_price' => 100,
                    'quantity' => 1,
                    'discount_amount' => 0.0,
                ],
                [
                    'unit_price' => 100,
                    'quantity' => 2,
                    'discount_amount' => 0.0,
                ]
        ]);

        $block = $this->jctTaxCalculator->calculateWithTaxNotInPrice(
            $items,
            10,
            $this->appliedRates10,
            'JPY'
        );
        $this->assertEquals(10, $block->getTaxPercent());
        $this->assertEquals(300, $block->getTotal());
        $this->assertEquals(330, $block->getTotalInclTax());
        $this->assertEquals(30, $block->getTax());
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

            $item->method('getUnitPrice')->willReturn($itemData['unit_price']);
            $item->method('getQuantity')->willReturn($itemData['quantity']);
            $item->method('getDiscountAmount')->willReturn($itemData['discount_amount']);

            $quoteDetailsItems[] = $item;
        }

        return $quoteDetailsItems;
    }

    private function mockCreateOrderItemAdapters(array $data = [])
    {
        $quoteDetailsItems = [];
        foreach ($data as $itemData) {
            $item = $this->getMockBuilder(OrderItemAdapter::class)
                ->disableOriginalConstructor()
                ->getMock();

            $item->method('getUnitPrice')->willReturn($itemData['unit_price']);
            $item->method('getQuantity')->willReturn($itemData['quantity']);
            $item->method('getDiscountAmount')->willReturn($itemData['discount_amount']);

            $quoteDetailsItems[] = $item;
        }

        return $quoteDetailsItems;
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
