/**
 * @api
 */

define([
  'Magento_Checkout/js/view/summary/abstract-total',
  'Magento_Checkout/js/model/quote',
  'Magento_Checkout/js/model/totals',
], function (Component, quote, totals) {
  'use strict';

  var displaySubtotalMode = window.checkoutConfig.reviewTotalsDisplayMode;

  return Component.extend({
      defaults: {
          displaySubtotalMode: displaySubtotalMode,
          template: 'Japan_Tax/checkout/summary/subtotal'
      },
      totals: quote.getTotals(),

      /**
       * @return {*|String}
       */
      getValue: function () {
          var price = 0;

          if (this.totals()) {
              price = this.totals().subtotal;
          }

          return this.getFormattedPrice(price);
      },

      /**
       * @return {*|String}
       */
      getJct10Value: function () {
        var price = 0;

        if (this.totals()) {
            price = totals.getSegment('subtotalExclJct10').value;
        }

        return this.getFormattedPrice(price);
      },

      /**
       * @return {*|String}
       */
      getJct8Value: function () {
        var price = 0;

        if (this.totals()) {
            price = totals.getSegment('subtotalExclJct8').value;
        }

        return this.getFormattedPrice(price);
      },

      /**
       * @return {Boolean}
       */
      isBothPricesDisplayed: function () {
          return this.displaySubtotalMode == 'both'; //eslint-disable-line eqeqeq
      },

      /**
       * @return {Boolean}
       */
      isIncludingTaxDisplayed: function () {
          return this.displaySubtotalMode == 'including'; //eslint-disable-line eqeqeq
      },

      /**
       * @return {*|String}
       */
      getValueInclTax: function () {
          var price = 0;

          if (this.totals()) {
              price = this.totals()['subtotal_incl_tax'];
          }

          return this.getFormattedPrice(price);
      },

      /**
       * @return {*|String}
       */
      getJct10ValueInclTax: function () {
        var price = 0;

        if (this.totals()) {
            price = totals.getSegment('subtotalInclJct10').value;
        }

        return this.getFormattedPrice(price);
      },

      /**
       * @return {*|String}
       */
      getJct8ValueInclTax: function () {
        var price = 0;

        if (this.totals()) {
            price = totals.getSegment('subtotalInclJct8').value;
        }

        return this.getFormattedPrice(price);
      },
  });
});
