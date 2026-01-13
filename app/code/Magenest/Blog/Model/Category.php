<?php
namespace Magenest\Blog\Model;

use Magento\Framework\Model\AbstractModel;
use Magenest\Blog\Model\ResourceModel\Category as CategoryResource;

class Category extends AbstractModel
{
    const CACHE_TAG = 'magenest_category';
    protected $_cacheTag = 'magenest_category';
    protected $_eventPrefix = 'magenest_category';

    protected function _construct()
    {
        $this->_init(CategoryResource::class);
    }
}