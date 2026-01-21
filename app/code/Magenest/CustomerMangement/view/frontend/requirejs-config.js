var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/action/set-shipping-information': {
                'Magenest_CustomerMangement/js/action/set-shipping-information-mixin': true
            },
            'Magento_Checkout/js/action/set-billing-address': {
                'Magenest_CustomerMangement/js/action/set-billing-address-mixin': true
            }
        }
    }
};
