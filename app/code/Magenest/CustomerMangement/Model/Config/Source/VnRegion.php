<?php
namespace Magenest\CustomerMangement\Model\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class VnRegion extends AbstractSource
{
    /**
     * Get all options
     *
     * @return array
     */
    public function getAllOptions()
    {
        if (null === $this->_options) {
            $this->_options = [
                ['value' => 1, 'label' => __('Miền Bắc')],
                ['value' => 2, 'label' => __('Miền Trung')],
                ['value' => 3, 'label' => __('Miền Nam')],
            ];
        }
        return $this->_options;
    }
    
    // Hàm này giúp lấy label từ value (VD: nhập 1 -> ra 'Miền Bắc')
    public function getOptionText($value)
    {
        foreach ($this->getAllOptions() as $option) {
            if ($option['value'] == $value) {
                return $option['label'];
            }
        }
        return false;
    }
}