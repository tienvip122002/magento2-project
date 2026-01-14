<?php
namespace Magenest\ColorSwitcher\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

class Colors extends AbstractFieldArray
{
    /**
     * @var ColorPicker
     */
    protected $colorPickerRenderer;

    /**
     * Hàm helper để load CSS của ColorPicker
     * Nếu không có hàm này, picker hiện ra sẽ bị vỡ giao diện
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        // Add CSS chuẩn của thư viện ColorPicker vào trang Admin
        $this->pageConfig->addPageAsset('jquery/colorpicker/css/colorpicker.css');
    }

    /**
     * Hàm lấy Renderer instance
     * (Singleton pattern: Chỉ tạo 1 lần dùng cho nhiều dòng)
     */
    protected function getColorPickerRenderer()
    {
        if (!$this->colorPickerRenderer) {
            $this->colorPickerRenderer = $this->getLayout()->createBlock(
                \Magenest\ColorSwitcher\Block\Adminhtml\Form\Field\ColorPicker::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->colorPickerRenderer;
    }

    protected function _prepareToRender()
    {
        // Cột 1: Tên màu (Text thường)
        $this->addColumn('color_name', [
            'label' => __('Color Name'),
            'class' => 'required-entry'
        ]);

        // Cột 2: Mã màu (Dùng Renderer Color Picker)
        $this->addColumn('color_code', [
            'label' => __('Color Code'),
            'renderer' => $this->getColorPickerRenderer(), // <--- CHÌA KHÓA Ở ĐÂY
            'class' => 'required-entry' // Validate bắt buộc nhập
        ]);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Color');
    }

    /**
     * Hàm này giúp gán dữ liệu vào các cột khi load lại trang
     * (Bắt buộc phải có khi dùng custom renderer)
     */
    protected function _prepareArrayRow(\Magento\Framework\DataObject $row)
    {
        $options = [];
        $colorPicker = $this->getColorPickerRenderer();
        
        // Key này giúp Magento map dữ liệu vào đúng cột renderer
        // Cấu trúc: 'option_extra_attrs' => ['option_' . $renderer->calcOptionHash($row->getData('key_cot')) => 'selected="selected"']
        // Nhưng với Input text thì đơn giản hơn, chủ yếu để map ID nếu cần.
        // Với bài toán này code mặc định của AbstractFieldArray thường tự handle được value của input.
        
        return parent::_prepareArrayRow($row);
    }
}