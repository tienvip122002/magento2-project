<?php
namespace Magenest\PromotionPopup\Plugin;

use Magento\Customer\CustomerData\Customer;
use Magento\Customer\Model\Session as CustomerSession;

class CustomerData
{
    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @param CustomerSession $customerSession
     */
    public function __construct(
        CustomerSession $customerSession
    ) {
        $this->customerSession = $customerSession;
    }

    /**
     * Add customer group id to customer data
     *
     * @param Customer $subject
     * @param array $result
     * @return array
     */
    public function afterGetSectionData(Customer $subject, array $result)
    {
        if ($this->customerSession->isLoggedIn()) {
            $result['group_id'] = $this->customerSession->getCustomerGroupId();
        } else {
            $result['group_id'] = 0;
        }
        return $result;
    }
}
