<?php
namespace Magenest\CustomerMangement\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Customer\Model\Session;
use Magento\Customer\Api\CustomerRepositoryInterface;

class B2bBanner extends Template
{
    protected $customerSession;
    protected $customerRepository;

    public function __construct(
        Context $context,
        Session $customerSession,
        CustomerRepositoryInterface $customerRepository,
        array $data = []
    ) {
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
        parent::__construct($context, $data);
    }

    public function isB2bCustomer()
    {
        // 1. Nếu chưa đăng nhập -> Ẩn luôn
        if (!$this->customerSession->isLoggedIn()) {
            return false;
        }

        try {
            // 2. Lấy ID khách hàng hiện tại
            $customerId = $this->customerSession->getCustomerId();
            
            // 3. Load thông tin đầy đủ
            $customer = $this->customerRepository->getById($customerId);
            
            // 4. Lấy giá trị is_b2b
            $isB2bAttribute = $customer->getCustomAttribute('is_b2b');

            // 5. Nếu có giá trị và bằng 1 (Yes) -> Trả về true
            if ($isB2bAttribute && $isB2bAttribute->getValue() == '1') {
                return true;
            }
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }
}