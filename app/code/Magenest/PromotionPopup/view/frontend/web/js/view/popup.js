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

        initialize: function () {
            this._super();
            var self = this;

            console.log('PromotionPopup component initialized');
            console.log('Raw customer_group_contents:', this.customer_group_contents);

            // 1. Kiểm tra LocalStorage (Khách mới hay cũ)
            var isShown = localStorage.getItem('magenest_popup_shown');

            if (isShown) {
                console.log('Popup already shown, skipping...');
                return; // Nếu đã hiện rồi thì thôi
            }

            // 2. Parse customer group contents từ this (component properties)
            var customerGroupContents = {};
            try {
                // If customer_group_contents is already an object, use it directly
                if (typeof this.customer_group_contents === 'object') {
                    customerGroupContents = this.customer_group_contents;
                } else if (typeof this.customer_group_contents === 'string') {
                    customerGroupContents = JSON.parse(this.customer_group_contents);
                } else {
                    console.error('customer_group_contents is undefined or invalid type');
                    return;
                }
            } catch (e) {
                console.error('Failed to parse customer group contents:', e);
                return;
            }

            console.log('Parsed customer group contents:', customerGroupContents);

            // 3. Xác định nội dung dựa trên customer group ID
            // Delay để đợi customer data load xong
            setTimeout(function () {
                // Lấy customer data từ localStorage/section
                var customer = customerData.get('customer')();

                console.log('Customer data:', customer);

                // Lấy customer group ID
                // Nếu không có customer data hoặc chưa login, group_id sẽ là 0 (NOT LOGGED IN)
                var customerGroupId = customer.group_id || 0;

                console.log('Customer Group ID:', customerGroupId);
                console.log('Available Contents:', customerGroupContents);

                // Lấy content tương ứng với customer group ID
                var content = customerGroupContents[customerGroupId] || '';

                if (!content) {
                    console.warn('No content configured for customer group:', customerGroupId);
                    return; // Không có content thì không hiển thị popup
                }

                console.log('Content to display:', content);
                self.popupContent(content);

                // 4. Hiển thị Popup - Delay thêm một chút để đảm bảo DOM đã ready
                setTimeout(function () {
                    console.log('Showing popup...');
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