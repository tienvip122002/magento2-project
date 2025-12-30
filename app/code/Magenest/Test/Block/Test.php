<?php
namespace Magenest\Test\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\Json\Helper\Data as JsonHelper;

class Test extends Template
{
    protected $layoutProcessors;
    protected $jsonHelper;

    public function __construct(
        Template\Context $context,
        JsonHelper $jsonHelper,  // Thêm JsonHelper vào constructor
        array $layoutProcessors = [],
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->jsonHelper = $jsonHelper;  // Khởi tạo JsonHelper
        $this->jsLayout = isset($data['jsLayout']) && is_array($data['jsLayout']) ? $data['jsLayout'] : [];
        $this->layoutProcessors = $layoutProcessors;
    }

    public function getJsLayout()
    {
        foreach ($this->layoutProcessors as $processor) {
            $this->jsLayout = $processor->process($this->jsLayout);
        }

        // Sử dụng JsonHelper để mã hóa mảng thành JSON
        return $this->jsonHelper->jsonEncode($this->jsLayout);
    }
}
