<?php
declare(strict_types=1);

namespace Magenest\CustomerAvatar\Block\Adminhtml\Customer\Edit\Tab;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Customer Avatar Tab Block - Display Only
 */
class Avatar extends Template implements \Magento\Ui\Component\Layout\Tabs\TabInterface
{
    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param CustomerRepositoryInterface $customerRepository
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        CustomerRepositoryInterface $customerRepository,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        $this->customerRepository = $customerRepository;
        $this->storeManager = $storeManager;
        parent::__construct($context, $data);
    }

    /**
     * Get current customer ID from request
     *
     * @return int|null
     */
    public function getCustomerId(): ?int
    {
        $customerId = $this->getRequest()->getParam('id');
        return $customerId ? (int) $customerId : null;
    }

    /**
     * Get customer avatar URL
     *
     * @return string|null
     */
    public function getAvatarUrl(): ?string
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

            $mediaUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);

            // Handle array format (from image uploader)
            if (is_array($avatarValue)) {
                if (isset($avatarValue[0]['url'])) {
                    return $avatarValue[0]['url'];
                }
                if (isset($avatarValue[0]['file'])) {
                    return $mediaUrl . ltrim($avatarValue[0]['file'], '/');
                }
            }

            // Handle string format
            if (is_string($avatarValue) && !empty($avatarValue)) {
                return $mediaUrl . ltrim($avatarValue, '/');
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if customer has avatar
     *
     * @return bool
     */
    public function hasAvatar(): bool
    {
        return $this->getAvatarUrl() !== null;
    }

    /**
     * Get customer name
     *
     * @return string
     */
    public function getCustomerName(): string
    {
        $customerId = $this->getCustomerId();
        if (!$customerId) {
            return '';
        }

        try {
            $customer = $this->customerRepository->getById($customerId);
            return $customer->getFirstname() . ' ' . $customer->getLastname();
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * @inheritdoc
     */
    public function getTabLabel()
    {
        return __('Customer Avatar');
    }

    /**
     * @inheritdoc
     */
    public function getTabTitle()
    {
        return __('Customer Avatar');
    }

    /**
     * @inheritdoc
     */
    public function canShowTab()
    {
        return $this->getCustomerId() !== null;
    }

    /**
     * @inheritdoc
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getTabClass()
    {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function getTabUrl()
    {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function isAjaxLoaded()
    {
        return false;
    }
}
