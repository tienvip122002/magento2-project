<?php
namespace Magenest\Blog\Controller\Adminhtml\Blog;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;
use Magenest\Blog\Model\BlogFactory;

class Edit extends Action
{
    protected $resultPageFactory;
    protected $blogFactory;

    public function __construct(
        Action\Context $context,
        PageFactory $resultPageFactory,
        BlogFactory $blogFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->blogFactory = $blogFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        // 1. Lấy ID từ URL
        $id = $this->getRequest()->getParam('id');
        $model = $this->blogFactory->create();

        // 2. Nếu có ID -> Load dữ liệu
        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This blog no longer exists.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }

        // 3. Tạo trang và set tiêu đề
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Magenest_Blog::manage_blog');
        
        $title = $id ? __('Edit Blog: %1', $model->getTitle()) : __('New Blog');
        $resultPage->getConfig()->getTitle()->prepend($title);

        return $resultPage;
    }
}