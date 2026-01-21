<?php

namespace Magenest\CustomerMangement\Plugin;

use Magento\Quote\Api\BillingAddressManagementInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\ResourceModel\Quote\Address as AddressResource;
use Psr\Log\LoggerInterface;
use Magento\Customer\Api\AddressRepositoryInterface;

class BillingAddressManagementPlugin
{
    protected CartRepositoryInterface $quoteRepository;
    protected LoggerInterface $logger;
    protected AddressRepositoryInterface $customerAddressRepository;
    protected AddressResource $addressResource;

    /**
     * Store vn_region value to be used in afterAssign
     */
    private ?string $vnRegionForBilling = null;

    public function __construct(
        CartRepositoryInterface $quoteRepository,
        LoggerInterface $logger,
        AddressRepositoryInterface $customerAddressRepository,
        AddressResource $addressResource
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->logger = $logger;
        $this->customerAddressRepository = $customerAddressRepository;
        $this->addressResource = $addressResource;
    }

    /**
     * Trước khi gán địa chỉ thanh toán (Billing Address).
     * Mục đích: Lấy giá trị vn_region (từ payload hoặc shipping address) và lưu tạm để dùng sau.
     *
     * @param BillingAddressManagementInterface $subject
     * @param int $cartId
     * @param AddressInterface $address Địa chỉ billing được gửi lên từ Frontend
     * @param bool $useForShipping
     */
    public function beforeAssign(
        BillingAddressManagementInterface $subject,
        $cartId,
        AddressInterface $address,
        $useForShipping = false
    ) {
        $this->logger->info('VN_REGION_BILLING_DEBUG: beforeAssign started for CartID: ' . $cartId);

        /** @var Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);
        $vnRegionValue = null;

        // 1. Thử lấy vn_region từ chính địa chỉ billing gửi lên
        $vnRegionValue = $this->getVnRegionFromAddress($address);
        $this->logger->info('VN_REGION_BILLING_DEBUG: vn_region from incoming address: ' . json_encode($vnRegionValue));

        // 2. Nếu không có (trường hợp user tick "Same as Shipping"), copy từ Shipping Address sang
        if ($vnRegionValue === null) {
            $shippingAddress = $quote->getShippingAddress();
            if ($shippingAddress && $shippingAddress->getData('vn_region')) {
                $vnRegionValue = $shippingAddress->getData('vn_region');
                $this->logger->info('VN_REGION_BILLING_DEBUG: Copied from shipping address: ' . $vnRegionValue);
            }
        }

        // 3. Nếu vẫn không có và đây là địa chỉ lấy từ Address Book, load từ Repository
        if ($vnRegionValue === null && $address->getCustomerAddressId()) {
            try {
                $customerAddressId = $address->getCustomerAddressId();
                $customerAddress = $this->customerAddressRepository->getById($customerAddressId);
                if ($customerAddress) {
                    $caAttribute = $customerAddress->getCustomAttribute('vn_region');
                    if ($caAttribute) {
                        $vnRegionValue = $caAttribute->getValue();
                        $this->logger->info('VN_REGION_BILLING_DEBUG: Loaded from customer address repo: ' . $vnRegionValue);
                    }
                }
            } catch (\Exception $e) {
                $this->logger->error('VN_REGION_BILLING_DEBUG: Error loading customer address: ' . $e->getMessage());
            }
        }

        // Lưu giá trị vào biến của class để dùng ở hàm afterAssign
        $this->vnRegionForBilling = $vnRegionValue;
        $this->logger->info('VN_REGION_BILLING_DEBUG: Stored vnRegionForBilling: ' . json_encode($this->vnRegionForBilling));

        return [$cartId, $address, $useForShipping];
    }

    /**
     * Sau khi gán địa chỉ thanh toán.
     * Mục đích: Lấy giá trị đã lưu tạm và force save vào database cho địa chỉ mới được tạo.
     *
     * @param BillingAddressManagementInterface $subject
     * @param int $result Quote ID
     * ...
     */
    public function afterAssign(
        BillingAddressManagementInterface $subject,
        $result,
        $cartId,
        AddressInterface $address,
        $useForShipping = false
    ) {
        $this->logger->info('VN_REGION_BILLING_DEBUG: afterAssign started for CartID: ' . $cartId);

        /** @var Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);
        /** @var \Magento\Quote\Model\Quote\Address $billingAddress */
        $billingAddress = $quote->getBillingAddress();

        $this->logger->info('VN_REGION_BILLING_DEBUG: Billing Address ID: ' . $billingAddress->getId());
        $this->logger->info('VN_REGION_BILLING_DEBUG: Stored vnRegionForBilling: ' . json_encode($this->vnRegionForBilling));

        // Sử dụng giá trị đã lưu ở beforeAssign
        if ($this->vnRegionForBilling !== null) {
            $billingAddress->setData('vn_region', $this->vnRegionForBilling);

            // Dùng Resource Model để lưu trực tiếp xuống DB, tránh bị Core Magento overwrite hoặc không save
            try {
                $this->addressResource->save($billingAddress);
                $this->logger->info('VN_REGION_BILLING_DEBUG: afterAssign - SAVED vn_region to DB via Resource: ' . $this->vnRegionForBilling);

                // Verify lại
                $savedValue = $billingAddress->getData('vn_region');
                $this->logger->info('VN_REGION_BILLING_DEBUG: afterAssign - Verified saved value: ' . json_encode($savedValue));
            } catch (\Exception $e) {
                $this->logger->error('VN_REGION_BILLING_DEBUG: Error saving billing address: ' . $e->getMessage());
            }
        } else {
            $this->logger->info('VN_REGION_BILLING_DEBUG: afterAssign - No vn_region to save');
        }

        // Reset biến để tránh ảnh hưởng request sau (dù PHP request life-cycle ngắn nhưng vẫn nên reset)
        $this->vnRegionForBilling = null;

        return $result;
    }

    /**
     * Helper function: Trích xuất vn_region từ object Address
     */
    private function getVnRegionFromAddress(AddressInterface $address): ?string
    {
        $vnRegionValue = null;

        // 1. Thử lấy từ Extension Attributes
        if ($address->getExtensionAttributes()) {
            $extAttrs = $address->getExtensionAttributes();
            if (method_exists($extAttrs, 'getVnRegion')) {
                $val = $extAttrs->getVnRegion();
                if ($val) {
                    $vnRegionValue = (string) $val;
                    $this->logger->info('VN_REGION_BILLING_DEBUG: Found in ExtensionAttributes: ' . $vnRegionValue);
                }
            }
        }

        // 2. Thử lấy từ Custom Attributes (dạng mảng key-value)
        if ($vnRegionValue === null) {
            $customAttributes = $address->getCustomAttributes();
            if (is_array($customAttributes)) {
                foreach ($customAttributes as $attribute) {
                    $code = is_array($attribute) ? ($attribute['attribute_code'] ?? null) : $attribute->getAttributeCode();
                    $value = is_array($attribute) ? ($attribute['value'] ?? null) : $attribute->getValue();

                    if ($code === 'vn_region') {
                        $vnRegionValue = (string) $value;
                        $this->logger->info('VN_REGION_BILLING_DEBUG: Found in CustomAttributes: ' . $vnRegionValue);
                        break;
                    }
                }
            }
        }

        return $vnRegionValue;
    }
}
