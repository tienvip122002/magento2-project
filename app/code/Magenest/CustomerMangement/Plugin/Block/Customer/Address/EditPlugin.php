<?php
namespace Magenest\CustomerMangement\Plugin\Block\Customer\Address;

use Magento\Customer\Block\Address\Edit;
use Magenest\CustomerMangement\ViewModel\VNRegionViewModel;

class EditPlugin
{
    protected $vnRegionViewModel;

    public function __construct(
        VNRegionViewModel $vnRegionViewModel
    ) {
        $this->vnRegionViewModel = $vnRegionViewModel;
    }

    /**
     * Force set template to our module's template
     * And inject ViewModel directly
     * 
     * @param Edit $subject
     * @return void
     */
    public function beforeToHtml(Edit $subject)
    {
        // Override template
        $subject->setTemplate('Magenest_CustomerMangement::address/edit.phtml');

        // Inject ViewModel directly into the block data
        // So in template we can use $block->getData('view_model_vnregion')
        $subject->setData('view_model_vnregion', $this->vnRegionViewModel);

        return null;
    }
}
