<?php
declare(strict_types=1);

namespace Magenest\CustomerAvatar\Block\Account\Dashboard;

use Magento\Customer\Model\Session;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;

class Avatar extends Template
{
    private Session $customerSession;
    private StoreManagerInterface $storeManager;

    public function __construct(
        Context $context,
        Session $customerSession,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
    }

    /**
     * Get current customer
     */
    public function getCustomer()
    {
        return $this->customerSession->getCustomer();
    }

    /**
     * Get avatar URL
     */
    public function getAvatarUrl(): ?string
    {
        $customer = $this->getCustomer();
        $avatarFileName = $customer->getData('avatar');

        if (!$avatarFileName || !is_string($avatarFileName)) {
            return null;
        }

        // Build full URL to avatar
        $mediaUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        return $mediaUrl . 'customer/' . $avatarFileName;
    }

    /**
     * Check if customer has avatar
     */
    public function hasAvatar(): bool
    {
        return $this->getAvatarUrl() !== null;
    }

    /**
     * Get customer name
     */
    public function getCustomerName(): string
    {
        $customer = $this->getCustomer();
        return $customer->getName();
    }

    /**
     * Get customer email
     */
    public function getCustomerEmail(): string
    {
        $customer = $this->getCustomer();
        return $customer->getEmail();
    }
}
