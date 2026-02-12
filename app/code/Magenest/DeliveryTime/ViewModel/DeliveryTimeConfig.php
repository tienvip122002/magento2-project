<?php
namespace Magenest\DeliveryTime\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\Registry;

class DeliveryTimeConfig implements ArgumentInterface
{
    /**
     * Registry để lấy thông tin sản phẩm hiện tại
     * @var Registry
     */
    protected $registry;

    /**
     * Constructor - Inject Registry dependency
     * @param Registry $registry Đối tượng registry của Magento
     */
    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Lấy Option ID của Custom Option "Delivery Time"
     * 
     * Hàm này tìm kiếm Custom Option dựa trên:
     * 1. SKU = 'delivery-time' (ưu tiên cao - không bị ảnh hưởng bởi đa ngôn ngữ)
     * 2. Title = 'Delivery Time' (fallback - phòng trường hợp SKU trống)
     *
     * @return int|null ID của option hoặc null nếu không tìm thấy
     */
    public function getDeliveryTimeOptionId()
    {
        // Lấy sản phẩm hiện tại từ Registry
        $product = $this->registry->registry('current_product');
        if (!$product) {
            return null;
        }

        // Duyệt qua tất cả Custom Options của sản phẩm
        foreach ($product->getOptions() as $option) {
            // CÁCH 1: Kiểm tra SKU trước (an toàn nhất - không bị ảnh hưởng khi đổi tên/dịch)
            if ($option->getSku() === 'delivery-time') {
                return $option->getOptionId();
            }

            // CÁCH 2 (Fallback): Kiểm tra Title nếu SKU rỗng hoặc không hợp lệ
            // Dùng so sánh strict (===) để tránh lỗi logic
            if ($option->getTitle() === 'Delivery Time') {
                return $option->getOptionId();
            }
        }

        // Fallback mặc định nếu không tìm thấy (giá trị cứng, nên tránh dùng)
        return 2;
    }
}
