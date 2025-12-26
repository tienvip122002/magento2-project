<?php
declare(strict_types=1);

namespace Magenest\Movie\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ForceCustomerFirstname implements ObserverInterface
{

    public function execute(Observer $observer): void
    {
        $customer = $observer->getEvent()->getCustomer();
        if (!$customer) {
            return;
        }

        if ((string)$customer->getFirstname() === 'Magenest') {
            return;
        }

        $customer->setData('firstname', 'Magenest');
    }
}
