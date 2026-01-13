<?php
namespace Magenest\Blog\Model\ResourceModel\Blog;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magenest\Blog\Model\Blog as BlogModel;
use Magenest\Blog\Model\ResourceModel\Blog as BlogResource;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'id';
    protected $_eventPrefix = 'magenest_blog_collection';
    protected $_eventObject = 'blog_collection';

    protected function _construct()
    {
        $this->_init(BlogModel::class, BlogResource::class);
    }
}