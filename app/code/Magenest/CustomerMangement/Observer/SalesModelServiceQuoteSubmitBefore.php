<?php
namespace Magenest\CustomerMangement\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class SalesModelServiceQuoteSubmitBefore implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $quote = $observer->getEvent()->getQuote();
        $order = $observer->getEvent()->getOrder();

        if ($quote->getShippingAddress()) {
            $order->getShippingAddress()->setData(
                'vn_region',
                $quote->getShippingAddress()->getData('vn_region')
            );
        }

        // Handle billing address if needed, though usually billing address is handled separately
        if ($quote->getBillingAddress()) {
            $order->getBillingAddress()->setData(
                'vn_region',
                $quote->getBillingAddress()->getData('vn_region')
            );
        }

        return $this;
    }
}
