/**
 * This file is part of Japanese Consumption Tax Extension For Magento2 the  project.
 *
 * Copyright (c) 2023 Adobe (or other copyright holders)
 *
 * For the full copyright and license information, please view the OSL-3.0
 * license that is bundled with this source code in the file LICENSE, or
 * at https://opensource.org/licenses/OSL-3.0
 */
/**
 * @api
 */

define([
    'Magento_Checkout/js/view/summary/abstract-total',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/totals',
    'mage/translate',
], function (Component, quote, totals, $t) {
    'use strict';

    var priceIncludesTax = window.checkoutConfig.priceIncludesTax;

    return Component.extend({
        defaults: {
            priceIncludesTax: priceIncludesTax,
            notCalculatedMessage: $t('Not yet calculated'),
            template: 'Magentoj_JapaneseConsumptionTax/checkout/summary/jct'
        },
        totals: quote.getTotals(),

        /**
         * @return {*|String}
         */
        getSubtotalExclJct10: function () {
            var price = 0;

            if (this.totals()) {
                price = totals.getSegment('subtotalExclJct10').value;
            }

            return this.getFormattedPrice(price);
        },

        /**
         * @return {*|String}
         */
        getSubtotalExclJct8: function () {
            var price = 0;

            if (this.totals()) {
                price = totals.getSegment('subtotalExclJct8').value;
            }

            return this.getFormattedPrice(price);
        },

        /**
         * @return {*|Boolean}
         */
        isCalculated: function () {
            return this.totals() && this.isFullMode() && totals.getSegment('tax') != null;
        },

        /**
         * @return {Boolean}
         */
        isTaxIncludedInPrice: function () {
            return this.priceIncludesTax;
        },

        /**
         * @return {*|String}
         */
        getSubtotalInclJct10: function () {
            var price = 0;

            if (this.totals()) {
                price = totals.getSegment('subtotalInclJct10').value;
            }

            return this.getFormattedPrice(price);
        },

        /**
         * @return {*|String}
         */
        getSubtotalInclJct8: function () {
            var price = 0;

            if (this.totals()) {
                price = totals.getSegment('subtotalInclJct8').value;
            }

            return this.getFormattedPrice(price);
        },

        /**
         * @return {*}
         */
        getJct10: function () {
            var amount;

            if (!this.isCalculated()) {
                return this.notCalculatedMessage;
            }
            amount = totals.getSegment('jct10').value;

            return this.priceIncludesTax ?
                '(' + this.getFormattedPrice(amount) + ')' : this.getFormattedPrice(amount);
        },

        /**
         * @return {*}
         */
        getJct8: function () {
            var amount;

            if (!this.isCalculated()) {
                return this.notCalculatedMessage;
            }
            amount = totals.getSegment('jct8').value;

            return this.priceIncludesTax ?
                '(' + this.getFormattedPrice(amount) + ')' : this.getFormattedPrice(amount);
        },
    });
});
