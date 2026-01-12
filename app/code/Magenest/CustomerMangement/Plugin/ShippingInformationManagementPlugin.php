<?php

namespace Magenest\CustomerMangement\Plugin;

use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Model\ShippingInformationManagement;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote\Address;
use Magento\Framework\App\RequestInterface;

class ShippingInformationManagementPlugin
{
    protected $quoteRepository;

    public function __construct(
        CartRepositoryInterface    $quoteRepository,
        protected RequestInterface $request,
    )
    {
        $this->quoteRepository = $quoteRepository;
    }

    // Magenest/CustomAddress/Plugin/ShippingInformationManagementPlugin.php
    public function beforeSaveAddressInformation(
        ShippingInformationManagement $subject,
                                      $cartId,
        ShippingInformationInterface  $addressInformation
    )
    {

        $address = $addressInformation->getShippingAddress();

        $quote = $this->quoteRepository->getActive($cartId);

        // Get custom attributes from the extension attributes
        $customAttributes = $address->getCustomAttributes();

        if (is_array($customAttributes)) {
            foreach ($customAttributes as $attribute) {
                if ($attribute['attribute_code'] === 'vn_region') {
                    $quote->getShippingAddress()->setData('vn_region', $attribute['value']);
                    break;
                }
            }
        }

        return [$cartId, $addressInformation];
    }

    public function afterSaveAddressInformation(
        ShippingInformationManagement $subject,
                                      $result,
                                      $cartId,
        ShippingInformationInterface  $addressInformation
    )
    {
        // Đảm bảo giá trị vn_region được lưu vào quote_address
        $quote = $this->quoteRepository->getActive($cartId);
        $shippingAddress = $quote->getShippingAddress();
        if ($vnRegion = $shippingAddress->getData('vn_region')) {
            $shippingAddress->setData('vn_region', $vnRegion);
            $shippingAddress->save();
        }

        return $result;
    }
}