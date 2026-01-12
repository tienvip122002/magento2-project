<?php

namespace Magenest\CustomerMangement\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magenest\CustomerMangement\Model\Config\Source\VnRegion;

class VNRegionViewModel implements ArgumentInterface
{
    protected $vnRegionSource;

    public function __construct(VnRegion $vnRegionSource)
    {
        $this->vnRegionSource = $vnRegionSource;
    }

    public function getOptions()
    {
        return $this->vnRegionSource ? $this->vnRegionSource->getAllOptions() : [];
    }
}