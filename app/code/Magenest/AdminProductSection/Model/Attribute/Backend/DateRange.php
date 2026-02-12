<?php
declare(strict_types=1);

namespace Magenest\AdminProductSection\Model\Attribute\Backend;

use Magento\Eav\Model\Entity\Attribute\Backend\AbstractBackend;
use Magento\Framework\Exception\LocalizedException;

class DateRange extends AbstractBackend
{
    /**
     * Validate date range
     *
     * @param \Magento\Framework\DataObject $object
     * @return bool
     * @throws LocalizedException
     */
    public function validate($object)
    {
        $logger = \Magento\Framework\App\ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class);
        $attrCode = $this->getAttribute()->getAttributeCode();
        $logger->info("DEBUG_DATE: Validating $attrCode for product ID: " . $object->getId());

        // 1. Lấy dữ liệu AN TOÀN (tránh lỗi Array/Null gây sập web)
        $attributeCode = $this->getAttribute()->getAttributeCode();
        $dateValue = $this->getSafeValue($object, $attributeCode);

        $fromDate = $this->getSafeValue($object, 'magenest_from_date');
        $toDate = $this->getSafeValue($object, 'magenest_to_date');

        $logger->info("DEBUG_DATE: Values - From: " . var_export($fromDate, true) . ", To: " . var_export($toDate, true) . ", Current: " . var_export($dateValue, true));

        // Case 0: Cả 2 đều rỗng -> Bỏ qua
        if (empty($fromDate) && empty($toDate)) {
            $logger->info("DEBUG_DATE: Both empty, skipping.");
            return true;
        }

        // Case 1: Có 1 thiếu 1 -> Báo lỗi
        if (($fromDate && !$toDate) || (!$fromDate && $toDate)) {
            $logger->info("DEBUG_DATE: Mismatch from/to");
            throw new LocalizedException(
                __('Both From Date and To Date must be set together.')
            );
        }

        // Case 2: Validate ngày 8-12 (Chỉ chạy nếu có value và là string hợp lệ)
        if ($dateValue) {
            $timestamp = strtotime($dateValue);
            $logger->info("DEBUG_DATE: Timestamp: " . var_export($timestamp, true));

            // Nếu format ngày tháng lạ lùng không parse được -> Bỏ qua để tránh lỗi
            if ($timestamp !== false) {
                // Fix timezone: Convert UTC date to Local date
                $timezone = \Magento\Framework\App\ObjectManager::getInstance()->get(\Magento\Framework\Stdlib\DateTime\TimezoneInterface::class);
                $dateTime = new \DateTime($dateValue);
                $localDate = $timezone->date($dateTime);
                $day = (int) $localDate->format('j');

                $logger->info("DEBUG_DATE: Day (UTC): " . date('j', $timestamp) . " -> Day (Local): " . $day);

                if ($day < 8 || $day > 12) {
                    $logger->info("DEBUG_DATE: Invalid day");
                    throw new LocalizedException(
                        __('%1 must be between day 8 and 12. You selected day %2.', $this->getAttribute()->getDefaultFrontendLabel(), $day)
                    );
                }
            }
        }

        // Case 3: So sánh From <= To
        // Chỉ cần check 1 lần khi đang đứng ở field 'to_date'
        if ($fromDate && $toDate && $attributeCode === 'magenest_to_date') {
            $fromTs = strtotime($fromDate);
            $toTs = strtotime($toDate);

            if ($fromTs !== false && $toTs !== false && $toTs < $fromTs) {
                $logger->info("DEBUG_DATE: To < From");
                throw new LocalizedException(
                    __('To Date must be greater than or equal to From Date.')
                );
            }
        }

        $logger->info("DEBUG_DATE: Passed");
        return true;
    }

    /**
     * Hàm quan trọng nhất: Chống crash cho strict_types
     * Chuyển mọi thể loại Array/Object/Null về String hoặc Null chuẩn
     */
    private function getSafeValue($object, $key)
    {
        $val = $object->getData($key);

        // Fix lỗi UI Component gửi mảng ['value' => '...']
        if (is_array($val)) {
            return $val['value'] ?? null;
        }

        // Fix lỗi sản phẩm cũ giá trị là null hoặc object lạ
        if (!is_string($val)) {
            return null;
        }

        return $val;
    }
}