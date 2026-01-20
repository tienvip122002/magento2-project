define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/checkout-data'
], function ($, wrapper, quote, checkoutData) {
    'use strict';

    return function (setShippingInformationAction) {

        return wrapper.wrap(setShippingInformationAction, function (originalAction) {
            var shippingAddress = quote.shippingAddress();

            if (!shippingAddress) {
                return originalAction();
            }

            if (shippingAddress['extension_attributes'] === undefined) {
                shippingAddress['extension_attributes'] = {};
            }

            var vnRegionValue = null;

            // 1. Try to find in customAttributes array (Standard Quote Object)
            if (Array.isArray(shippingAddress.customAttributes)) {
                var attribute = shippingAddress.customAttributes.find(
                    function (element) {
                        return element.attribute_code === 'vn_region';
                    }
                );
                if (attribute) {
                    vnRegionValue = attribute.value;
                }
            }

            // 2. Fallback: Check if it's in custom_attributes object (non-array on Quote Object)
            if (!vnRegionValue && shippingAddress.custom_attributes && shippingAddress.custom_attributes['vn_region']) {
                vnRegionValue = shippingAddress.custom_attributes['vn_region'];
                if (typeof vnRegionValue === 'object' && vnRegionValue.value) {
                    vnRegionValue = vnRegionValue.value;
                }
            }

            // 3. Last Result: Check checkout-data (Form Data)
            if (!vnRegionValue) {
                var shippingAddressData = checkoutData.getShippingAddressFromData();
                if (shippingAddressData && shippingAddressData.custom_attributes && shippingAddressData.custom_attributes['vn_region']) {
                    var val = shippingAddressData.custom_attributes['vn_region'];
                    // Check if object or scalar
                    if (typeof val === 'object' && val.value) {
                        vnRegionValue = val.value;
                    } else {
                        vnRegionValue = val;
                    }
                }
            }

            if (vnRegionValue) {
                shippingAddress['extension_attributes']['vn_region'] = vnRegionValue;
            }

            return originalAction();
        });
    };
});
