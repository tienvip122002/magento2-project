define([
    'uiComponent',
    'ko',
    'jquery',
    'jquery/ui'
], function (Component, ko, $) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Magenest_DeliveryTime/delivery-ui',
            selectedOption: ko.observable('same_day'),
            customDate: ko.observable(''),
            targetInputSelector: 'input[name^="options"]'
        },

        initialize: function () {
            this._super();
            var self = this;

            console.log('DeliveryTime Component initialized');

            // Wait for DOM to be ready
            $(document).ready(function () {
                // Find target input
                self.targetInput = $(self.targetInputSelector).first();

                if (self.targetInput.length) {
                    self.targetInput.parents('.field').hide();
                    console.log('Target input found and hidden');
                }

                // Update value function
                self.updateValue = function () {
                    var finalValue = '';
                    var today = new Date();
                    var dateString = today.toLocaleDateString("en-US");

                    if (self.selectedOption() === 'same_day') {
                        finalValue = "Same Day Delivery (" + dateString + ")";
                        self.customDate('');
                    } else {
                        finalValue = "Selected Date: " + self.customDate();
                    }

                    console.log('Updating value to Magento input:', finalValue);

                    if (self.targetInput && self.targetInput.length) {
                        self.targetInput.val(finalValue);
                        self.targetInput.trigger('change');
                    }
                };

                // Subscribe to changes
                self.selectedOption.subscribe(function (newValue) {
                    console.log('Selected option changed to:', newValue);
                    self.updateValue();
                });

                self.customDate.subscribe(function (newValue) {
                    console.log('Custom date changed to:', newValue);
                    self.updateValue();
                });

                // Set default value
                self.updateValue();
            });

            return this;
        },

        // Init datepicker using afterRender binding
        initDatepicker: function (element) {
            var self = this;
            console.log('Initializing datepicker on element:', element);

            $(element).datepicker({
                dateFormat: 'mm/dd/yy',
                minDate: 0,
                onSelect: function (dateText) {
                    console.log('Date selected:', dateText);
                    self.customDate(dateText);
                }
            });
        }
    });
});