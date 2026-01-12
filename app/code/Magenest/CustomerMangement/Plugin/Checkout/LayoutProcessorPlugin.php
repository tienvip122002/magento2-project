<?php

namespace Magenest\CustomerMangement\Plugin\Checkout;

class LayoutProcessorPlugin
{
    protected $vnRegionSource;

    public function __construct(
        \Magenest\CustomerMangement\Model\Config\Source\VnRegion $vnRegionSource
    )
    {
        $this->vnRegionSource = $vnRegionSource;
    }

    // Magenest/CustomerMangement/Plugin/Checkout/LayoutProcessorPlugin.php
    public function afterProcess($subject, array $jsLayout)
    {
        $code = 'vn_region';
        $field = [
            'component' => 'Magento_Ui/js/form/element/select',
            'config' => [
                'customScope' => 'shippingAddress.custom_attributes',
                'template' => 'ui/form/field',
                'elementTmpl' => 'ui/form/element/select',
            ],
            'dataScope' => "shippingAddress.custom_attributes.$code",
            'label' => 'Miền (VN Region)',
            'provider' => 'checkoutProvider',
            'sortOrder' => 100,
            'options' => $this->vnRegionSource->toOptionArray(),
            'visible' => true,
        ];

        // Shipping
        $jsLayout['components']['checkout']['children']['steps']['children']
        ['shipping-step']['children']['shippingAddress']['children']
        ['shipping-address-fieldset']['children'][$code] = $field;

        // Billing (giả sử checkmo; chỉnh path tuỳ phương thức)
        $billingPath =& $jsLayout['components']['checkout']['children']['steps']['children']
        ['billing-step']['children']['payment']['children']
        ['afterMethods']['children']['billingAddress']
        ['children']['form-fields']['children'];
        $billingPath[$code] = array_merge($field, [
            'config' => ['customScope' => 'billingAddress.custom_attributes'],
            'dataScope' => "billingAddress.custom_attributes.$code",
        ]);

        return $jsLayout;
    }

}