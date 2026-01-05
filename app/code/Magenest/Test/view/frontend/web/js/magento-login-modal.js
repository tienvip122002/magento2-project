define([
    'jquery',
    'Magento_Ui/js/modal/modal'
], function ($, modal) {
    'use strict';

    return function (config, element) {
        $(element).on('click', function () {
            // Magento có sẵn authentication-popup
            // Trigger click vào element hiện có
            var authPopup = $('[data-block="customer-authentication-popup"]');

            if (authPopup.length) {
                authPopup.modal('openModal');
            } else {
                alert('Authentication popup not found on this page');
            }
        });
    };
});
