<?php
namespace Magenest\SourceTime\Controller\Demo;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magenest\SourceTime\Helper\Data as Helper;
use Magento\Customer\Model\Session;
use Magento\Customer\Api\CustomerRepositoryInterface;

class Check extends Action
{
    protected $helper;
    protected $customerSession;
    protected $customerRepository;

    public function __construct(
        Context $context,
        Helper $helper,
        Session $customerSession,
        CustomerRepositoryInterface $customerRepository
    ) {
        parent::__construct($context);
        $this->helper = $helper;
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
    }

    public function execute()
    {
        $productId = $this->getRequest()->getParam('product_id');
        $customerId = $this->getRequest()->getParam('customer_id');

        echo "<div style='font-family: sans-serif; padding: 20px;'>";
        echo "<h1>Demo Check Access Module Magenest_SourceTime</h1>";

        if (!$productId) {
            echo "<p style='color:red; font-weight:bold;'>Vui lòng thêm tham số ?product_id=X vào URL để kiểm tra sản phẩm.</p>";
            echo "</div>";
            return;
        }

        // Ưu tiên lấy Customer ID từ URL để test nhanh
        if ($customerId) {
            echo "<p>Đang kiểm tra với Customer ID (từ URL): <strong>$customerId</strong></p>";
            try {
                $customer = $this->customerRepository->getById($customerId);
                $groupId = $customer->getGroupId();
            } catch (\Exception $e) {
                echo "<p style='color:red'>Lỗi: Không tìm thấy Customer ID $customerId trong hệ thống.</p>";
                echo "</div>";
                return;
            }
        } elseif ($this->customerSession->isLoggedIn()) {
            // Nếu không truyền ID thì lấy user đang login
            $customerId = $this->customerSession->getCustomerId();
            $groupId = $this->customerSession->getCustomerGroupId();
            echo "<p>Đang kiểm tra với Customer đang đăng nhập: <strong>ID $customerId</strong></p>";
        } else {
            echo "<p style='color:orange; font-weight:bold;'>Cảnh báo: Bạn chưa đăng nhập.</p>";
            echo "<p>-> Vui lòng đăng nhập tài khoản Customer ngoài frontend, hoặc thêm tham số <code>&customer_id=Y</code> vào URL để giả lập.</p>";
            echo "</div>";
            return;
        }

        echo "<ul>";
        echo "<li>Product ID cần check: <strong>$productId</strong></li>";
        echo "<li>Customer Group ID: <strong>$groupId</strong></li>";
        echo "</ul>";

        // VÀO LOGIC CHÍNH
        // Gọi Helper để check
        $canAccess = $this->helper->canAccessProduct($customerId, $productId, $groupId);

        echo "<hr style='border: 1px dashed #ccc; margin: 20px 0;'>";

        if ($canAccess) {
            echo "<h2 style='color:green; border: 2px solid green; display:inline-block; padding: 10px;'>✅ KẾT QUẢ: ĐƯỢC PHÉP TRUY CẬP (ACCESS GRANTED)</h2>";
            echo "<p><strong>Lý do:</strong> Khách hàng này CÓ đơn hàng chứa sản phẩm này (đã Complete/Processing) và vẫn CÒN thời hạn xem (dựa theo cấu hình Group).</p>";
        } else {
            echo "<h2 style='color:red; border: 2px solid red; display:inline-block; padding: 10px;'>❌ KẾT QUẢ: BỊ CHẶN (ACCESS DENIED)</h2>";
            echo "<p><strong>Lý do có thể là:</strong></p>";
            echo "<ul>";
            echo "<li>Khách chưa từng mua sản phẩm này (hoặc đơn hàng chưa Complete).</li>";
            echo "<li>Cấu hình 'Duration per Customer Group' trong Admin chừa cài đặt cho Group ID $groupId (mặc định return 0 ngày -> chặn).</li>";
            echo "<li>Đã quá hạn số ngày cho phép kể từ ngày mua hàng.</li>";
            echo "</ul>";
        }
        echo "</div>";
    }
}
