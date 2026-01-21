define([
  'mage/utils/wrapper',
  'Magento_Checkout/js/model/quote'
], function (wrapper, quote) {
  'use strict';

  return function (setBillingAddressAction) {
    return wrapper.wrap(setBillingAddressAction, function (originalAction, messageContainer) {
      var shipping = quote.shippingAddress();

      // core chạy set billing
      originalAction(messageContainer);

      var billing = quote.billingAddress();
      if (!shipping || !billing) return;

      var shipExt = shipping.extension_attributes || {};
      var billExt = billing.extension_attributes || {};

      // copy khi billing thiếu
      if (shipExt.vn_region != null && shipExt.vn_region !== '' && (billExt.vn_region == null || billExt.vn_region === '')) {
        billExt.vn_region = shipExt.vn_region;
        billing.extension_attributes = billExt;
      }
    });
  };
});
