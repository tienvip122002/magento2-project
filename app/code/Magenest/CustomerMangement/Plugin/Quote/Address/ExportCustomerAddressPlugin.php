<?php
namespace Magenest\CustomerMangement\Plugin\Quote\Address;

use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Magento\Customer\Api\Data\AddressInterface as CustomerAddressInterface;
use Psr\Log\LoggerInterface;

class ExportCustomerAddressPlugin
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * @param QuoteAddress $subject
     * @param CustomerAddressInterface $result
     * @return CustomerAddressInterface
     */
    public function afterExportCustomerAddress(QuoteAddress $subject, CustomerAddressInterface $result)
    {
        $this->logger->info('VN_REGION_DEBUG: ExportCustomerAddressPlugin called');

        $vnRegion = $subject->getData('vn_region');
        $this->logger->info('VN_REGION_DEBUG: Quote Address vn_region value: ' . json_encode($vnRegion));

        if ($vnRegion) {
            // Set as Custom Attribute for EAV persistence
            $result->setCustomAttribute('vn_region', $vnRegion);
            $this->logger->info('VN_REGION_DEBUG: Set custom attribute vn_region on CustomerAddress result.');

            // Also set as extension attribute if available/needed
            $extensionAttributes = $result->getExtensionAttributes();
            if ($extensionAttributes) {
                // Check setter by method existence
                if (method_exists($extensionAttributes, 'setVnRegion')) {
                    $extensionAttributes->setVnRegion($vnRegion);
                    $this->logger->info('VN_REGION_DEBUG: Set extension attribute vn_region.');
                }
                $result->setExtensionAttributes($extensionAttributes);
            }
        } else {
            $this->logger->info('VN_REGION_DEBUG: No vn_region found in Quote Address object.');
        }

        return $result;
    }
}
