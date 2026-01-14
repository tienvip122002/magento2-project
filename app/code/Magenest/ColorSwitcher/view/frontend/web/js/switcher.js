define([
    'uiComponent',
    'ko',
    'jquery'
], function (Component, ko, $) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Magenest_ColorSwitcher/switcher',
            selectedColor: ko.observable('default'),
            availableColors: ko.observableArray([]) // FIX: Phải là observableArray
        },

        initialize: function (config) {
            this._super();
            var self = this;

            console.log('DEBUG: ColorSwitcher component initialized');
            console.log('DEBUG: Config options =', config.options);

            // Lấy danh sách options từ PHP truyền sang
            if (config.options && config.options.length > 0) {
                this.availableColors(config.options);
                console.log('DEBUG: availableColors set to', this.availableColors());
            } else {
                console.error('DEBUG: No options provided!');
            }

            // SUBSCRIBE: Lắng nghe sự thay đổi của dropdown
            this.selectedColor.subscribe(function (newValue) {
                console.log('Selected Color:', newValue);
                self.changeBackgroundColor(newValue);
            });

            return this;
        },

        changeBackgroundColor: function (colorCode) {
            if (colorCode === 'default') {
                // Nếu chọn default, xóa style background inline đi để về CSS gốc
                $('body').css('background-color', '');
            } else {
                // Nếu chọn màu, set CSS inline cho body
                $('body').css('background-color', colorCode);
            }
        }
    });
});