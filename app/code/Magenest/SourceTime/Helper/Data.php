<?php
namespace Magenest\SourceTime\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Framework\Serialize\Serializer\Json;

class Data extends AbstractHelper
{
    // Đường dẫn tới field config trong system.xml
    const XML_PATH_DURATION_MAP = 'source_time/general/duration_map';

    protected $orderCollectionFactory;
    protected $json;

    public function __construct(
        Context $context,
        OrderCollectionFactory $orderCollectionFactory,
        Json $json
    ) {
        parent::__construct($context);
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->json = $json;
    }

    /**
     * Hàm chính: Check xem khách (customerId) có được xem sản phẩm (productId) không
     * Dựa trên Group ID của khách.
     */
    public function canAccessProduct($customerId, $productId, $customerGroupId)
    {
        // // Ví dụ logic bổ sung:
        // $product = $this->productRepository->getById($productId);
        // $attributeSetId = $product->getAttributeSetId();

        // // Giả sử ID của set Course là 4 (hoặc query db để lấy ID theo tên 'Course')
        // if ($attributeSetId != $COURSE_SET_ID) {
        //     return true; // Nếu không phải khóa học -> Không áp dụng giới hạn thời gian (hoặc false tùy logic)
        // }
        // 1. Lấy số ngày cho phép từ Config
        $allowedDays = $this->getDurationFromConfig($customerGroupId);
        
        // Nếu config = 0 hoặc không set -> Chặn luôn (hoặc cho phép tùy logic bạn muốn)
        if ($allowedDays <= 0) {
            return false;
        }

        // 2. Tìm đơn hàng gần nhất khách đã mua sản phẩm này
        $lastOrderDate = $this->getLastPurchaseDate($customerId, $productId);
        
        if (!$lastOrderDate) {
            return false; // Chưa mua -> Chặn
        }

        // 3. Tính toán
        $purchaseTimestamp = strtotime($lastOrderDate);
        $now = time();
        
        // Tính thời gian hết hạn = Ngày mua + Số ngày cho phép
        // (86400 là số giây trong 1 ngày)
        $expireTimestamp = $purchaseTimestamp + ($allowedDays * 86400);

        // Nếu Hiện tại < Hết hạn -> Cho phép
        if ($now <= $expireTimestamp) {
            return true;
        }

        return false; // Hết hạn
    }

    /**
     * Lấy ngày tạo đơn hàng gần nhất
     */
    private function getLastPurchaseDate($customerId, $productId)
    {
        $collection = $this->orderCollectionFactory->create()
            ->addFieldToSelect('created_at')
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('status', ['in' => ['complete', 'processing']]) // Chỉ đơn đã thanh toán
            ->setOrder('created_at', 'DESC')
            ->setPageSize(1);

        // Join bảng item để tìm đúng sản phẩm
        $collection->getSelect()->join(
            ['item' => $collection->getTable('sales_order_item')],
            'main_table.entity_id = item.order_id',
            []
        )->where('item.product_id = ?', $productId);

        $order = $collection->getFirstItem();
        
        return $order->getId() ? $order->getCreatedAt() : null;
    }

    /**
     * Đọc và parse Config JSON
     */
    private function getDurationFromConfig($groupId)
    {
        $configVal = $this->scopeConfig->getValue(self::XML_PATH_DURATION_MAP);
        
        if (!$configVal) {
            return 0;
        }

        try {
            $map = $this->json->unserialize($configVal);
        } catch (\Exception $e) {
            return 0;
        }

        if (is_array($map)) {
            foreach ($map as $row) {
                if (isset($row['customer_group_id']) && $row['customer_group_id'] == $groupId) {
                    return (int)$row['duration_days'];
                }
            }
        }
        
        return 0;
    }
}