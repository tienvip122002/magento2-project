<?php
namespace Magenest\Blog\Model\ResourceModel\Category;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magenest\Blog\Model\Category as CategoryModel;
use Magenest\Blog\Model\ResourceModel\Category as CategoryResource;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'id';
    protected $_eventPrefix = 'magenest_category_collection';
    protected $_eventObject = 'category_collection';

    protected function _construct()
    {
        $this->_init(CategoryModel::class, CategoryResource::class);
    }
}