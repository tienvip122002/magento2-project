<?php
namespace Magenest\Blog\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface; 
use Magenest\Blog\Api\Data\BlogInterface;
use Magenest\Blog\Model\ResourceModel\Blog as BlogResource;

// 2. Implement Interface
class Blog extends AbstractModel implements IdentityInterface , BlogInterface
{
    const CACHE_TAG = 'magenest_blog';
    protected $_cacheTag = 'magenest_blog';
    protected $_eventPrefix = 'magenest_blog';

    protected function _construct()
    {
        $this->_init(BlogResource::class);
    }

    // 3. Định nghĩa hàm getIdentities (Bắt buộc)
    public function getIdentities()
    {
        // Trả về Tag chung (list) và Tag riêng (detail)
        // Ví dụ: [magenest_blog, magenest_blog_15]
        return [self::CACHE_TAG, self::CACHE_TAG . '_' . $this->getId()];
    }


    public function getId() { return $this->getData(self::ID); }
    public function setId($id) { return $this->setData(self::ID, $id); }

    public function getTitle() { return $this->getData(self::TITLE); }
    public function setTitle($title) { return $this->setData(self::TITLE, $title); }

    public function getContent() { return $this->getData(self::CONTENT); }
    public function setContent($content) { return $this->setData(self::CONTENT, $content); }

    public function getStatus() { return $this->getData(self::STATUS); }
    public function setStatus($status) { return $this->setData(self::STATUS, $status); }

    public function getUrlRewrite() { return $this->getData(self::URL_REWRITE); }
    public function setUrlRewrite($urlRewrite) { return $this->setData(self::URL_REWRITE, $urlRewrite); }

    public function getAuthorId() { return $this->getData(self::AUTHOR_ID); }
    public function setAuthorId($authorId) { return $this->setData(self::AUTHOR_ID, $authorId); }
}