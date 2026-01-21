<?php
declare(strict_types=1);

namespace Magenest\CustomerAvatar\Block\Account\Dashboard;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;

class Avatar extends Template
{
    private Session $customerSession;
    private StoreManagerInterface $storeManager;
    private CustomerRepositoryInterface $customerRepository;

    public function __construct(
        Context $context,
        Session $customerSession,
        StoreManagerInterface $storeManager,
        CustomerRepositoryInterface $customerRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->customerRepository = $customerRepository;
    }

    /**
     * Get current customer ID
     */
    public function getCustomerId(): ?int
    {
        $customerId = $this->customerSession->getCustomerId();
        return $customerId ? (int) $customerId : null;
    }

    /**
     * Get current customer from session (for basic info)
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
        $customerId = $this->getCustomerId();
        if (!$customerId) {
            return null;
        }

        try {
            // Use repository to load full customer with custom attributes
            $customer = $this->customerRepository->getById($customerId);
            $avatarAttribute = $customer->getCustomAttribute('avatar');

            if (!$avatarAttribute) {
                return null;
            }

            $avatarFileName = $avatarAttribute->getValue();

            if (!$avatarFileName) {
                return null;
            }

            // Build full URL to avatar
            $mediaUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);

            // Handle array format (from image uploader)
            if (is_array($avatarFileName) && isset($avatarFileName[0]['url'])) {
                return $avatarFileName[0]['url'];
            }

            if (is_array($avatarFileName) && isset($avatarFileName[0]['file'])) {
                return $mediaUrl . ltrim($avatarFileName[0]['file'], '/');
            }

            // Handle string format (direct path)
            if (is_string($avatarFileName) && !empty($avatarFileName)) {
                return $mediaUrl . ltrim($avatarFileName, '/');
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
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
        return $customer->getName() ?: '';
    }

    /**
     * Get customer email
     */
    public function getCustomerEmail(): string
    {
        $customer = $this->getCustomer();
        return $customer->getEmail() ?: '';
    }

    /**
     * Get avatar file path (relative path for form)
     * 
     * @return string|null
     */
    public function getAvatarPath(): ?string
    {
        $customerId = $this->getCustomerId();
        if (!$customerId) {
            return null;
        }

        try {
            $customer = $this->customerRepository->getById($customerId);
            $avatarAttribute = $customer->getCustomAttribute('avatar');

            if (!$avatarAttribute) {
                return null;
            }

            $avatarValue = $avatarAttribute->getValue();

            if (!$avatarValue) {
                return null;
            }

            // Handle array format 
            if (is_array($avatarValue) && isset($avatarValue[0]['file'])) {
                return $avatarValue[0]['file'];
            }

            // Handle string format
            if (is_string($avatarValue) && !empty($avatarValue)) {
                return $avatarValue;
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
