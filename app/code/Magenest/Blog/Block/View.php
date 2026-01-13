<?php
namespace Magenest\Blog\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\Registry;
use Magento\User\Model\UserFactory; // Factory để load bảng admin_user
use Magento\Framework\DataObject\IdentityInterface; // Quan trọng cho Cache

class View extends Template implements IdentityInterface
{
    protected $registry;
    protected $userFactory;

    public function __construct(
        Template\Context $context,
        Registry $registry,
        UserFactory $userFactory,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->userFactory = $userFactory;
        parent::__construct($context, $data);
    }

    /**
     * Lấy bài blog hiện tại từ Registry (do Controller gửi sang)
     */
    public function getCurrentBlog()
    {
        return $this->registry->registry('current_blog');
    }

    /**
     * Logic lấy tên tác giả bài blog hiện tại
     */
    public function getAuthorName()
    {
        $blog = $this->getCurrentBlog();
        if ($blog && $blog->getAuthorId()) {
            // Load model Admin User theo ID , nên join left ở collection vì nếu có nhiều bài viết câu truy vấn này sẽ chạy tất cả để lấy ra tất cả tác giả nên rất nặng truy vẫn web sẽ chậm 
            $adminUser = $this->userFactory->create()->load($blog->getAuthorId());
            if ($adminUser->getId()) {
                return $adminUser->getFirstname() . ' ' . $adminUser->getLastname();
            }
        }   
        return 'Admin'; // Mặc định nếu không tìm thấy
    }

    /**
     * QUAN TRỌNG: Implement IdentityInterface cho Block
     * Để Magento biết Block này phụ thuộc vào cache tag nào.
     */
    public function getIdentities()
    {
        $blog = $this->getCurrentBlog();
        if ($blog) {
            // Gọi lại hàm getIdentities() mà ta đã viết trong Model Blog lúc trước
            return $blog->getIdentities();
        }
        return [];
    }
}