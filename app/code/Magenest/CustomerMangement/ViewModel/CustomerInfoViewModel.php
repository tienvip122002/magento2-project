<?php
namespace Magenest\CustomerMangement\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Customer\Model\Session;
use Magento\Customer\Api\CustomerRepositoryInterface;

class CustomerInfoViewModel implements ArgumentInterface
{
    protected $customerSession;
    protected $customerRepository;

    public function __construct(
        Session $customerSession,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
    }

    public function getAccountTypeText()
    {
        if (!$this->customerSession->isLoggedIn()) {
            return '';
        }

        try {
            $customerId = $this->customerSession->getCustomerId();
            $customer = $this->customerRepository->getById($customerId);

            $isB2b = $customer->getCustomAttribute('is_b2b');
            if ($isB2b && $isB2b->getValue() == 1) {
                return __('B2B Account');
            }
        } catch (\Exception $e) {
            // Handle exception or return default
        }

        return __('Regular Account');
    }
}
