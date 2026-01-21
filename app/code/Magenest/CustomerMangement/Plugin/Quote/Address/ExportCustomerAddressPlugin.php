<?php
namespace Magenest\CustomerMangement\Plugin\Quote\Address;

use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Magento\Customer\Api\Data\AddressInterface as CustomerAddressInterface;
use Psr\Log\LoggerInterface;

class ExportCustomerAddressPlugin
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * Plugin can thiệp SAU khi địa chỉ Quote được export thành địa chỉ Khách hàng.
     * Chức năng: Đảm bảo custom attribute 'vn_region' được copy sang địa chỉ KH khi user chọn "Lưu vào sổ địa chỉ".
     *
     * @param QuoteAddress $subject Đối tượng địa chỉ trên Quote (Checkout)
     * @param CustomerAddressInterface $result Đối tượng địa chỉ Khách hàng (Address Book) vừa được tạo ra
     * @return CustomerAddressInterface
     */
    public function afterExportCustomerAddress(QuoteAddress $subject, CustomerAddressInterface $result)
    {
        $this->logger->info('VN_REGION_DEBUG: ExportCustomerAddressPlugin called');

        // 1. Lấy giá trị vn_region từ địa chỉ Quote (địa chỉ đang checkout)
        $vnRegion = $subject->getData('vn_region');
        $this->logger->info('VN_REGION_DEBUG: Quote Address vn_region value: ' . json_encode($vnRegion));

        if ($vnRegion) {
            // 2. Set giá trị vn_region vào Custom Attribute của Customer Address
            // Điều này đảm bảo EAV (Entity-Attribute-Value) model sẽ lưu được xuống DB
            $result->setCustomAttribute('vn_region', $vnRegion);
            $this->logger->info('VN_REGION_DEBUG: Set custom attribute vn_region on CustomerAddress result.');

            // 3. Ngoài ra, set luôn vào Extension Attributes nếu có
            // Một số logic API hoặc module khác có thể sẽ đọc từ Extension Attributes thay vì Custom Attributes
            $extensionAttributes = $result->getExtensionAttributes();
            if ($extensionAttributes) {
                // Kiểm tra xem interface Extension Attributes có hỗ trợ setter cho vn_region không
                if (method_exists($extensionAttributes, 'setVnRegion')) {
                    $extensionAttributes->setVnRegion($vnRegion);
                    $this->logger->info('VN_REGION_DEBUG: Set extension attribute vn_region.');
                }
                $result->setExtensionAttributes($extensionAttributes);
            }
        } else {
            $this->logger->info('VN_REGION_DEBUG: No vn_region found in Quote Address object.');
        }

        return $result;
    }
}
