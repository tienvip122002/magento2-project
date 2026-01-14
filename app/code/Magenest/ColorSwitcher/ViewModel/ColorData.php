<?php
namespace Magenest\ColorSwitcher\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Serialize\Serializer\Json;

class ColorData implements ArgumentInterface
{
    protected $scopeConfig;
    protected $json;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Json $json
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->json = $json;
    }

    public function getColorOptions()
    {
        // Lấy chuỗi JSON từ database - FIX: Đổi từ 'magenest_color' sang 'magenest'
        $configValue = $this->scopeConfig->getValue(
            'magenest/general/colors',
            ScopeInterface::SCOPE_STORE
        );

        // Default colors nếu chưa config trong admin
        $result = [];
        $result[] = [
            'label' => 'Default',
            'value' => 'default'
        ];

        if (!$configValue) {
            // Hardcode một vài màu mặc định để test
            $result[] = [
                'label' => 'Red',
                'value' => '#ff0000'
            ];
            $result[] = [
                'label' => 'Blue',
                'value' => '#0000ff'
            ];
            $result[] = [
                'label' => 'Green',
                'value' => '#00ff00'
            ];
            $result[] = [
                'label' => 'Purple',
                'value' => '#800080'
            ];
            return $result;
        }

        // Decode JSON thành mảng PHP
        $options = $this->json->unserialize($configValue);

        // Convert array object thành mảng tuần tự để JS dễ đọc
        foreach ($options as $option) {
            $result[] = [
                'label' => $option['color_name'],
                'value' => $option['color_code'] // Mã màu hex (ví dụ #ff0000)
            ];
        }

        return $result;
    }
}