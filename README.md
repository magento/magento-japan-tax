# Japanese Consumption Tax Extension For Magento2
This module provides the correct tax calculation and qualified invoices as required by the Qualified Invoice System which will be effective from October 2023 under the Japanese Consumption Tax (JCT) law.

## Tax Information
The following rows will be added to totals at the checkout, order, and invoice views.

- Subtotal Subject to 10% Tax
- Subtotal Subject to 8% Tax
- 10% Tax
- 8% Tax

The subtotals will be shown with tax included if catalog prices are including tax. The catalog price setting can be checked at `Stores > Configuration > SALES > Tax > Calculation Settings > Catalog Prices`.

A tax registration number (usually 14 characters, beginning with "T", followed by the 13-digit Japan corporate number) will be added to PDF invoices if it is configured as mentioned below in the Configuration section.

## Calculation
Only products that are assigned to tax class with the tax rate of 8% or 10% will be included in the calculation.

### Rounding Method
The default currency rounding method is Round Down (round towards zero). Install the module `CommunityEngineering/CurrencyPrecision` from [this repository](https://github.com/magento/magento2-jp) and configure the settings in `Stores > Configuration > GENERAL > Currency Setup > Currency Options > Currency Rounding Method` if you need to use other methods such as Ceiling and Half up.

## Configuration
The settings of this module are located in `Stores > Configuration > SALES > Tax > Japanese Consumption Tax System Settings`.

### Registration Number
You can put a tax registration number into the field and it will be appeared after the store address on the top right corner in the PDF invoice.

## Run unit tests

```
./vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist app/code/Magentoj/JapaneseConsumptionTax/Test/Unit/
```

## Run linter

```
vendor/bin/phpcs --standard=Magento2 app/code/Magentoj/JapaneseConsumptionTax/
```

## Run formatter

```
vendor/bin/phpcbf --standard=Magento2 app/code/Magentoj/JapaneseConsumptionTax/
```
