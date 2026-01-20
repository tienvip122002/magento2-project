<?php

namespace Magenest\CustomerMangement\Plugin;

use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Model\ShippingInformationManagement;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote\Address;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;
use Magento\Customer\Api\AddressRepositoryInterface;

class ShippingInformationManagementPlugin
{
    protected $quoteRepository;
    protected $customerAddressRepository;
    protected $logger;

    public function __construct(
        CartRepositoryInterface $quoteRepository,
        protected RequestInterface $request,
        LoggerInterface $logger,
        AddressRepositoryInterface $customerAddressRepository
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->logger = $logger;
        $this->customerAddressRepository = $customerAddressRepository;
    }

    // Magenest/CustomAddress/Plugin/ShippingInformationManagementPlugin.php
    public function beforeSaveAddressInformation(
        ShippingInformationManagement $subject,
        $cartId,
        ShippingInformationInterface $addressInformation
    ) {
        $this->logger->info('VN_REGION_DEBUG: beforeSaveAddressInformation started for CartID: ' . $cartId);

        $address = $addressInformation->getShippingAddress();
        $this->logger->info('VN_REGION_DEBUG: Incoming Address Data: ' . json_encode($address->getData()));

        $quote = $this->quoteRepository->getActive($cartId);

        $vnRegionValue = null;

        // PRIORITY FIX:
        // If Customer Address ID exists, prioritize loading from Repository/DB over Frontend Payload.
        // This avoids issues where frontend might send a default "1" for an existing address that has "2".
        if ($address->getCustomerAddressId()) {
            try {
                $customerAddressId = $address->getCustomerAddressId();
                $this->logger->info("VN_REGION_DEBUG: Address has ID ($customerAddressId). Prioritizing Repository Lookup.");

                $customerAddress = $this->customerAddressRepository->getById($customerAddressId);
                if ($customerAddress) {
                    $caAttribute = $customerAddress->getCustomAttribute('vn_region');
                    if ($caAttribute) {
                        $dbValue = $caAttribute->getValue();
                        $this->logger->info("VN_REGION_DEBUG: Loaded vn_region from Repo (Priority): " . $dbValue);
                        if ($dbValue) {
                            $vnRegionValue = $dbValue;
                        }
                    } else {
                        $this->logger->info("VN_REGION_DEBUG: Address loaded but has no vn_region custom attribute.");
                    }
                }
            } catch (\Exception $e) {
                $this->logger->error("VN_REGION_DEBUG: Error loading customer address: " . $e->getMessage());
            }
        }

        // Only check payload if vnRegionValue is still NULL (meaning it's a NEW address or DB didn't have it)
        if ($vnRegionValue === null) {
            $this->logger->info("VN_REGION_DEBUG: Checking payload for vn_region (New Address or not in DB)...");

            // 1. Try Extension Attributes
            if ($address->getExtensionAttributes()) {
                $extAttrs = $address->getExtensionAttributes();
                $val = $extAttrs->getVnRegion();
                if ($val) {
                    $vnRegionValue = $val;
                    $this->logger->info('VN_REGION_DEBUG: Taken from ExtensionAttribute: ' . json_encode($val));
                }
            } else {
                $this->logger->info('VN_REGION_DEBUG: No ExtensionAttributes on incoming address.');
            }

            // 2. Try Custom Attributes
            if ($vnRegionValue === null) {
                $customAttributes = $address->getCustomAttributes();
                if (is_array($customAttributes)) {
                    foreach ($customAttributes as $attribute) {
                        // Check if attribute is array or object
                        $code = is_array($attribute) ? ($attribute['attribute_code'] ?? null) : $attribute->getAttributeCode();
                        $value = is_array($attribute) ? ($attribute['value'] ?? null) : $attribute->getValue();

                        if ($code === 'vn_region') {
                            $vnRegionValue = $value;
                            $this->logger->info('VN_REGION_DEBUG: Found vn_region in CustomAttributes: ' . print_r($value, true));
                            break;
                        }
                    }
                }
            }
        }

        if ($vnRegionValue !== null) {
            $this->logger->info("VN_REGION_DEBUG: Setting vn_region to quote address: " . $vnRegionValue);
            $quote->getShippingAddress()->setData('vn_region', $vnRegionValue);
        } else {
            $this->logger->info("VN_REGION_DEBUG: vn_region value NOT found in incoming payload AND could not be loaded from Customer Address.");
        }

        return [$cartId, $addressInformation];
    }

    public function afterSaveAddressInformation(
        ShippingInformationManagement $subject,
        $result,
        $cartId,
        ShippingInformationInterface $addressInformation
    ) {
        $quote = $this->quoteRepository->getActive($cartId);
        $shippingAddress = $quote->getShippingAddress();
        if ($vnRegion = $shippingAddress->getData('vn_region')) {
            $shippingAddress->setData('vn_region', $vnRegion);
            $shippingAddress->save();
        }

        return $result;
    }
}