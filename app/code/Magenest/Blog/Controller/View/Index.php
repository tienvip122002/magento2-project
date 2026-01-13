<?php
namespace Magenest\Blog\Controller\View;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magenest\Blog\Model\BlogFactory;
use Magento\Framework\Registry;

class Index extends Action
{
    protected $resultPageFactory;
    protected $blogFactory;
    protected $registry;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        BlogFactory $blogFactory,
        Registry $registry
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->blogFactory = $blogFactory;
        $this->registry = $registry;
        parent::__construct($context);
    }

    public function execute()
    {
        // 1. Lấy ID từ URL
        $blogId = $this->getRequest()->getParam('id');
        $blog = $this->blogFactory->create()->load($blogId);

        // 2. Kiểm tra bài viết có tồn tại và đang bật (Status=1) không
        if (!$blog->getId() || $blog->getStatus() != 1) {
            // Nếu không có, chuyển hướng về trang 404
            $resultForward = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_FORWARD);
            return $resultForward->forward('noroute');
        }

        // 3. Đăng ký biến blog vào Registry để Block có thể lấy dùng
        $this->registry->register('current_blog', $blog);

        // 4. Load Layout
        return $this->resultPageFactory->create();
    }
}