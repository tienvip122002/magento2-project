define([
    'jquery',
    'Magento_Ui/js/modal/alert'
], function ($, alert) {
    'use strict';

    return function (config, element) {
        $(element).on('click', function () {
            alert({
                title: 'Hello World!',
                content: 'This is a simple alert modal',
                actions: {
                    always: function() {
                        console.log('Alert modal closed');
                    }
                }
            });
        });
    };
});
