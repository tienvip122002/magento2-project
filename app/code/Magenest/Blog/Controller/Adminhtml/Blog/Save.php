<?php
namespace Magenest\Blog\Controller\Adminhtml\Blog;

use Magento\Backend\App\Action;
use Magenest\Blog\Model\BlogFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Backend\Model\Auth\Session as AuthSession; // 1. Thêm class Auth Session

class Save extends Action
{
    protected $blogFactory;
    protected $dataPersistor;
    protected $authSession; // 2. Khai báo biến

    public function __construct(
        Action\Context $context,
        BlogFactory $blogFactory,
        DataPersistorInterface $dataPersistor,
        AuthSession $authSession // 3. Inject vào constructor
    ) {
        $this->blogFactory = $blogFactory;
        $this->dataPersistor = $dataPersistor;
        $this->authSession = $authSession; // Gán vào biến
        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        
        $data = $this->getRequest()->getPostValue();

        if ($data) {
            // Xử lý status
            if (isset($data['status']) && $data['status'] === 'true') {
                $data['status'] = 1;
            }
            if (empty($data['id'])) {
                $data['id'] = null;
            }

            $model = $this->blogFactory->create();
            $id = $this->getRequest()->getParam('id');
            
            // Biến tạm để lưu tác giả cũ nếu đang edit
            $existingAuthorId = null;

            if ($id) {
                $model->load($id);
                // Lưu lại author_id cũ trước khi bị setData() ghi đè
                $existingAuthorId = $model->getAuthorId();
            }

            // Gán dữ liệu mới từ form
            $model->setData($data);

            // --- LOGIC XỬ LÝ TÁC GIẢ ---
            
            // Lấy thông tin Admin đang đăng nhập
            $currentUser = $this->authSession->getUser();
            
            if (!$id) {
                // TRƯỜNG HỢP 1: THÊM MỚI (New)
                // Gán tác giả là người đang đăng nhập
                if ($currentUser) {
                    $model->setAuthorId($currentUser->getId());
                }
            } else {
                // TRƯỜNG HỢP 2: CHỈNH SỬA (Edit)
                // Logic: Nếu muốn giữ nguyên tác giả ban đầu, ta gán lại ID cũ
                // (Vì hàm setData($data) ở trên có thể đã làm mất author_id nếu form không gửi lên)
                if ($existingAuthorId) {
                    $model->setAuthorId($existingAuthorId);
                }
                
                // MỞ RỘNG: Nếu bạn muốn người sửa cuối cùng (Last Modified By) là tác giả mới
                // thì bỏ đoạn if($existingAuthorId) ở trên và dùng đoạn dưới này:
                /*
                if ($currentUser) {
                    $model->setAuthorId($currentUser->getId());
                }
                */
            }
            // ---------------------------

            try {
                $model->save();
                
                $this->messageManager->addSuccessMessage(__('You saved the blog.'));
                $this->dataPersistor->clear('magenest_blog');

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['id' => $model->getId()]);
                }
                return $resultRedirect->setPath('*/*/');

            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('Something went wrong while saving the blog.'));
            }

            $this->dataPersistor->set('magenest_blog', $data);
            return $resultRedirect->setPath('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
        }
        return $resultRedirect->setPath('*/*/');
    }
}