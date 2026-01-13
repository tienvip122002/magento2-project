<?php
namespace Magenest\Blog\Controller\Adminhtml\Blog;

use Magento\Backend\App\Action;

class NewAction extends Action
{
    public function execute()
    {
        // Chuyển tiếp sang controller Edit
        return $this->resultForwardFactory->create()->forward('edit');
    }
}