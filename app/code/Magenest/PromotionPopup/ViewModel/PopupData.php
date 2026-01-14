<?php
namespace Magenest\PromotionPopup\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class PopupData implements ArgumentInterface
{
    protected $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function isEnabled()
    {
        return $this->scopeConfig->getValue('promotion_popup/general/enable', ScopeInterface::SCOPE_STORE);
    }

    public function getPopupConfig()
    {
        return [
            'guest_content' => $this->scopeConfig->getValue('promotion_popup/general/content_guest', ScopeInterface::SCOPE_STORE),
            'member_content' => $this->scopeConfig->getValue('promotion_popup/general/content_member', ScopeInterface::SCOPE_STORE)
        ];
    }
}