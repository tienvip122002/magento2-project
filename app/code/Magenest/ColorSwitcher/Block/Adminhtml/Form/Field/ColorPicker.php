<?php
namespace Magenest\ColorSwitcher\Block\Adminhtml\Form\Field;

use Magento\Framework\View\Element\AbstractBlock;

class ColorPicker extends AbstractBlock
{
    /**
     * Hàm này set name cho input để Magento lưu được dữ liệu
     * (Dynamic Row sẽ tự động gọi hàm này)
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * Hàm này set ID cho input
     */
    public function setInputId($value)
    {
        return $this->setId($value);
    }

    /**
     * Render HTML ra ô input + Script kích hoạt Color Picker
     */
    protected function _toHtml()
    {
        // 1. Tạo ô input text bình thường
        // Thêm style width: 100px cho gọn
        $html = '<input type="text" name="' . $this->getName() . '" id="' . $this->getId() . '" ';
        $html .= 'value="' . $this->escapeHtml($this->getValue()) . '" ';
        $html .= 'class="input-text admin__control-text color-picker-input" style="width: 100px"/>';

        // 2. Thêm Script kích hoạt Color Picker của Magento
        // Ta dùng widget 'jquery/colorpicker/js/colorpicker' có sẵn của Core
        $html .= '
        <script type="text/javascript">
            require(["jquery", "jquery/colorpicker/js/colorpicker"], function ($) {
                $(document).ready(function () {
                    var $el = $("#' . $this->getId() . '");
                    // Kích hoạt colorpicker
                    $el.ColorPicker({
                        color: "' . $this->escapeHtml($this->getValue()) . '",
                        onChange: function (hsb, hex, rgb) {
                            // Khi chọn màu -> Gán ngược lại vào input
                            $el.css("backgroundColor", "#" + hex).val("#" + hex);
                        }
                    });
                    // Set màu nền ban đầu cho input nếu đã có giá trị
                    if($el.val()) {
                        $el.css("backgroundColor", $el.val());
                    }
                });
            });
        </script>';

        return $html;
    }
}