<?php
namespace Magenest\PromotionPopup\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Serialize\Serializer\Json;

class PopupData implements ArgumentInterface
{
    protected $scopeConfig;
    protected $jsonSerializer;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Json $jsonSerializer
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->jsonSerializer = $jsonSerializer;
    }

    public function isEnabled()
    {
        return $this->scopeConfig->getValue('promotion_popup/general/enable', ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get popup config with all customer group contents
     * Returns JSON string for frontend consumption
     */
    public function getPopupConfig()
    {
        $serializedData = $this->scopeConfig->getValue(
            'promotion_popup/general/customer_group_content',
            ScopeInterface::SCOPE_STORE
        );

        $customerGroupContents = [];
        if ($serializedData) {
            try {
                // Try to unserialize (old format) or decode JSON
                $data = @unserialize($serializedData);
                if ($data === false && $serializedData !== 'b:0;') {
                    $data = $this->jsonSerializer->unserialize($serializedData);
                }
                $customerGroupContents = is_array($data) ? $data : [];
            } catch (\Exception $e) {
                // Log error if needed, or suppress
                $customerGroupContents = [];
            }
        }

        return $this->jsonSerializer->serialize($customerGroupContents);
    }
}