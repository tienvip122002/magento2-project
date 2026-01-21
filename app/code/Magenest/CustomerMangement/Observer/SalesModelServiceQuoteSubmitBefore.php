<?php
namespace Magenest\CustomerMangement\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Psr\Log\LoggerInterface;

class SalesModelServiceQuoteSubmitBefore implements ObserverInterface
{
    protected LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Observer thực thi trước khi Quote được chuyển đổi thành Order.
     * Mục đích: Copy dữ liệu custom attribute 'vn_region' từ Quote Address sang Order Address.
     *
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        // 1. Lấy đối tượng Quote và Order từ event
        $quote = $observer->getEvent()->getQuote();
        $order = $observer->getEvent()->getOrder();

        $this->logger->info('VN_REGION_ORDER_DEBUG: SalesModelServiceQuoteSubmitBefore started');

        // 2. Xử lý Shipping Address: Copy vn_region từ Quote Shipping -> Order Shipping
        if ($quote->getShippingAddress() && $order->getShippingAddress()) {
            // Lấy giá trị vn_region từ bảng tạm quote_address (shipping)
            $shippingVnRegion = $quote->getShippingAddress()->getData('vn_region');
            $this->logger->info('VN_REGION_ORDER_DEBUG: Quote Shipping vn_region: ' . json_encode($shippingVnRegion));

            // Nếu có dữ liệu, gán nó vào bảng chính sales_order_address (shipping)
            if ($shippingVnRegion) {
                $order->getShippingAddress()->setData('vn_region', $shippingVnRegion);
                $this->logger->info('VN_REGION_ORDER_DEBUG: Set Order Shipping vn_region: ' . $shippingVnRegion);
            }
        }

        // 3. Xử lý Billing Address: Copy vn_region từ Quote Billing -> Order Billing
        if ($quote->getBillingAddress() && $order->getBillingAddress()) {
            // Lấy giá trị vn_region từ bảng tạm quote_address (billing)
            $billingVnRegion = $quote->getBillingAddress()->getData('vn_region');
            $this->logger->info('VN_REGION_ORDER_DEBUG: Quote Billing vn_region: ' . json_encode($billingVnRegion));

            // **Cơ chế Backup quan trọng**: 
            // Nếu Billing Address trên Quote không có vn_region (thường xảy ra do lỗi logic frontend hoặc core magento overwrite),
            // ta sẽ thử cứu dữ liệu bằng cách copy từ Shipping Address sang (giả định trường hợp "Same as Shipping").
            if (!$billingVnRegion && $quote->getShippingAddress()) {
                $billingVnRegion = $quote->getShippingAddress()->getData('vn_region');
                $this->logger->info('VN_REGION_ORDER_DEBUG: Billing vn_region copied from Shipping: ' . json_encode($billingVnRegion));
            }

            // Nếu tìm thấy dữ liệu (từ billing gốc hoặc copy từ shipping), gán vào bảng chính sales_order_address (billing)
            if ($billingVnRegion) {
                $order->getBillingAddress()->setData('vn_region', $billingVnRegion);
                $this->logger->info('VN_REGION_ORDER_DEBUG: Set Order Billing vn_region: ' . $billingVnRegion);
            }
        }

        return $this;
    }
}
