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
namespace Magentoj\JapaneseConsumptionTax\Plugin\Pdf\Items;

use Magento\Framework\App\ObjectManager;
use Magento\Sales\Model\RtlTextHandler;

class DefaultInvoice
{
    /**
     * Core string
     *
     * @var \Magento\Framework\Stdlib\StringUtils
     */
    protected $string;

    /**
     * @var RtlTextHandler
     */
    private $rtlTextHandler;

    public function __construct(
        \Magento\Framework\Stdlib\StringUtils $string
    ) {
        $this->string = $string;
        $this->rtlTextHandler = ObjectManager::getInstance()->get(RtlTextHandler::class);
    }

    public function aroundDraw(
        \Magento\Sales\Model\Order\Pdf\Items\Invoice\DefaultInvoice $subject,
        callable $proceed
    ) {
        $order = $subject->getOrder();
        $item = $subject->getItem();
        $pdf = $subject->getPdf();
        $page = $subject->getPage();
        $lines = [];

        // draw Product name
        $lines[0][] = [
            'text' => $this->string->split($this->prepareText((string)$item->getName()), 35, true, true),
            'feed' => 35
        ];

        // draw SKU
        $lines[0][] = [
            'text' => $this->string->split($this->prepareText((string)$subject->getSku($item)), 17),
            'feed' => 290,
            'align' => 'right',
        ];

        // draw QTY
        $lines[0][] = ['text' => $item->getQty() * 1, 'feed' => 405, 'align' => 'right'];

        // draw item Prices
        $i = 0;
        $prices = $subject->getItemPricesForDisplay();
        $feedPrice = 360;
        $feedSubtotal = $feedPrice + 205;
        foreach ($prices as $priceData) {
            if (isset($priceData['label'])) {
                // draw Price label
                $lines[$i][] = ['text' => $priceData['label'], 'feed' => $feedPrice, 'align' => 'right'];
                // draw Subtotal label
                $lines[$i][] = ['text' => $priceData['label'], 'feed' => $feedSubtotal, 'align' => 'right'];
                $i++;
            }
            // draw Price
            $lines[$i][] = [
                'text' => $priceData['price'],
                'feed' => $feedPrice,
                'font' => 'bold',
                'align' => 'right',
            ];
            // draw Subtotal
            $lines[$i][] = [
                'text' => $priceData['subtotal'],
                'feed' => $feedSubtotal,
                'font' => 'bold',
                'align' => 'right',
            ];
            $i++;
        }

        // draw Tax
        $lines[0][] = [
            'text' => $order->formatPriceTxt($item->getTaxAmount()),
            'feed' => 465,
            'font' => 'bold',
            'align' => 'right',
        ];

        // draw Tax Rate
        $lines[0][] = [
            'text' => floatval($item->getOrderItem()->getTaxPercent()) . '%',
            'feed' => 505,
            'font' => 'bold',
            'align' => 'right',
        ];

        // custom options
        $options = $subject->getItemOptions();
        if ($options) {
            foreach ($options as $option) {
                // draw options label
                $lines[][] = [
                    'text' => $this->string->split($subject->filterManager->stripTags($option['label']), 40, true, true),
                    'font' => 'italic',
                    'feed' => 35,
                ];

                // Checking whether option value is not null
                if ($option['value'] !== null) {
                    if (isset($option['print_value'])) {
                        $printValue = $option['print_value'];
                    } else {
                        $printValue = $subject->filterManager->stripTags($option['value']);
                    }
                    $values = explode(', ', $printValue);
                    foreach ($values as $value) {
                        $lines[][] = ['text' => $this->string->split($value, 30, true, true), 'feed' => 40];
                    }
                }
            }
        }

        $lineBlock = ['lines' => $lines, 'height' => 20];

        $page = $pdf->drawLineBlocks($page, [$lineBlock], ['table_header' => true]);
        $subject->setPage($page);
    }

    private function prepareText(string $string): string
    {
        return $this->rtlTextHandler->reverseRtlText(html_entity_decode($string));
    }
}
