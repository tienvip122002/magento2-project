define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'mage/validation'
], function ($, modal) {
    'use strict';

    return function (config, element) {
        var options = {
            type: 'popup',
            responsive: true,
            innerScroll: true,
            title: 'Login',
            buttons: []
        };

        var popup = modal(options, $('#login-popup-modal'));

        $(element).on('click', function () {
            $('#login-popup-modal').modal('openModal');
        });

        // Handle form submission
        $('#login-form').on('submit', function (e) {
            e.preventDefault();

            if ($(this).validation('isValid')) {
                var email = $('#login-email').val();
                var password = $('#login-password').val();

                console.log('Login attempt:', {email: email, password: password});

                // Here you can send AJAX request to login
                alert('Login functionality would be implemented here');

                $('#login-popup-modal').modal('closeModal');
            }
        });
    };
});
