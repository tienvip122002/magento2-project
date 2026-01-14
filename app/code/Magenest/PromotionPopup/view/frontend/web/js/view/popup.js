define([
    'uiComponent',
    'ko',
    'Magento_Customer/js/customer-data',
    'jquery'
], function (Component, ko, customerData, $) {
    'use strict';


    return Component.extend({
        defaults: {
            template: 'Magenest_PromotionPopup/popup-content',
            isVisible: ko.observable(false),
            popupContent: ko.observable('')
        },

        initialize: function (config) {
            this._super();
            var self = this;

            // 1. Kiểm tra LocalStorage (Khách mới hay cũ)
            var isShown = localStorage.getItem('magenest_popup_shown');

            if (isShown) {
                return; // Nếu đã hiện rồi thì thôi
            }

            // 2. Xác định nội dung dựa trên trạng thái đăng nhập và hiển thị popup
            // Delay để đợi customer data load xong
            setTimeout(function () {
                // Lấy customer data từ localStorage/section
                var customer = customerData.get('customer')();

                // Check nếu customer có fullname hoặc firstname thì là đã login
                var isLoggedIn = !!(customer.fullname || customer.firstname);

                if (isLoggedIn) {
                    self.popupContent(config.member_content);
                } else {
                    self.popupContent(config.guest_content);
                }

                // 3. Hiển thị Popup - Delay thêm một chút để đảm bảo DOM đã ready
                setTimeout(function () {
                    self.isVisible(true);

                    // Đánh dấu là đã hiện để lần sau không hiện nữa
                    localStorage.setItem('magenest_popup_shown', 'true');
                }, 300); // Delay thêm 300ms cho DOM
            }, 500); // Delay 500ms để đợi customer data load đầy đủ
        },


        // Hàm lấy nội dung để bind ra HTML
        getContent: function () {
            return this.popupContent();
        },

        // Hàm đóng popup (gắn vào nút Close nếu cần custom)
        closePopup: function () {
            this.isVisible(false);
        }
    });
});