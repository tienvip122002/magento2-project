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
            selectedOption: ko.observable('same_day'),  // Lựa chọn mặc định: giao trong ngày
            customDate: ko.observable(''),               // Ngày tùy chọn (nếu user chọn)
            targetInputSelector: 'input[name^="options"]', // Selector để tìm input Custom Option
            optionId: null  // ID của Custom Option (được truyền từ backend)
        },

        /**
         * Khởi tạo component
         * - Tìm input gốc của Custom Option
         * - Ẩn giao diện cũ
         * - Bind sự kiện khi user thay đổi lựa chọn
         */
        initialize: function () {
            this._super();
            var self = this;

            console.log('DeliveryTime Component đã khởi tạo');

            // Đợi DOM sẵn sàng
            $(document).ready(function () {
                // Tìm input gốc của Custom Option

                // CHIẾN LƯỢC 1: Sử dụng Option ID cụ thể (được truyền từ backend qua ViewModel)
                if (self.optionId) {
                    self.targetInput = $('input[name="options[' + self.optionId + ']"]');
                    console.log('Tìm input với Option ID:', self.optionId, '- Kết quả:', self.targetInput.length > 0);
                }

                // CHIẾN LƯỢC 2 (Fallback): Tìm theo Label Name nếu không tìm thấy bằng ID
                if (!self.targetInput || !self.targetInput.length) {
                    console.log('Delivery Time: Không tìm thấy input theo ID. Đang tìm theo Label...');

                    // Duyệt qua tất cả input có name bắt đầu bằng "options"
                    $(self.targetInputSelector).each(function () {
                        var input = $(this);
                        var id = input.attr('id');
                        // Tìm text của label. Thường nằm trong: <label for="id"><span>Text</span></label>
                        var labelText = $('label[for="' + id + '"]').text() || input.closest('label').text() || '';

                        // Kiểm tra xem label có chứa "Delivery" hoặc "Time" không (không phân biệt hoa thường)
                        if (labelText && (labelText.toLowerCase().indexOf('delivery') !== -1 || labelText.toLowerCase().indexOf('time') !== -1)) {
                            self.targetInput = input;
                            console.log('Đã tìm thấy input theo Label:', labelText);
                            return false; // Thoát khỏi vòng lặp each()
                        }
                    });
                }

                // CHIẾN LƯỢC 3 (Cuối cùng): Lấy option đầu tiên nếu không tìm được
                if (!self.targetInput || !self.targetInput.length) {
                    console.warn('Delivery Time: Không thể tìm input cụ thể. Sử dụng option đầu tiên.');
                    self.targetInput = $(self.targetInputSelector).first();
                }

                // Ẩn giao diện input gốc của Magento
                if (self.targetInput.length) {
                    self.targetInput.parents('.field').hide();
                    console.log('Đã tìm thấy và ẩn input gốc');
                }

                /**
                 * Hàm cập nhật giá trị vào input ẩn
                 * Được gọi mỗi khi user thay đổi lựa chọn (Radio hoặc Datepicker)
                 */
                self.updateValue = function () {
                    var finalValue = '';
                    var today = new Date();
                    var dateString = today.toLocaleDateString("en-US");

                    if (self.selectedOption() === 'same_day') {
                        // Người dùng chọn "Giao trong ngày"
                        finalValue = "Same Day Delivery (" + dateString + ")";
                        self.customDate(''); // Xóa ngày tùy chọn
                    } else {
                        // Người dùng chọn ngày cụ thể
                        finalValue = "Selected Date: " + self.customDate();
                    }

                    console.log('Đang cập nhật giá trị vào input Magento:', finalValue);

                    // Gán giá trị vào input ẩn và trigger sự kiện change
                    if (self.targetInput && self.targetInput.length) {
                        self.targetInput.val(finalValue);
                        self.targetInput.trigger('change'); // Báo cho Magento biết để validate
                    }
                };

                // Đăng ký lắng nghe thay đổi từ Radio Button
                self.selectedOption.subscribe(function (newValue) {
                    console.log('Lựa chọn đã thay đổi thành:', newValue);
                    self.updateValue();
                });

                // Đăng ký lắng nghe thay đổi từ Datepicker
                self.customDate.subscribe(function (newValue) {
                    console.log('Ngày tùy chọn đã thay đổi thành:', newValue);
                    self.updateValue();
                });

                // Thiết lập giá trị mặc định khi trang load
                self.updateValue();
            });

            return this;
        },

        /**
         * Khởi tạo jQuery UI Datepicker
         * Được gọi qua binding "afterRender" trong template
         * 
         * @param {HTMLElement} element - Element input cần khởi tạo datepicker
         */
        initDatepicker: function (element) {
            var self = this;
            console.log('Đang khởi tạo datepicker cho element:', element);

            $(element).datepicker({
                dateFormat: 'mm/dd/yy',    // Định dạng ngày: tháng/ngày/năm
                minDate: 0,                 // Không cho chọn ngày trong quá khứ
                onSelect: function (dateText) {
                    console.log('Đã chọn ngày:', dateText);
                    self.customDate(dateText); // Cập nhật observable
                }
            });
        }
    });
});