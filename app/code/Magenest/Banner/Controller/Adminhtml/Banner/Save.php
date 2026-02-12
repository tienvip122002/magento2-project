<?php
namespace Magenest\Banner\Controller\Adminhtml\Banner;

use Magento\Backend\App\Action;
use Magenest\Banner\Model\BannerFactory;
use Magento\Catalog\Model\ImageUploader;
use Psr\Log\LoggerInterface;

class Save extends Action
{
    protected $bannerFactory;
    protected $imageUploader;
    protected $logger; 
    public function __construct(
        Action\Context $context,
        BannerFactory $bannerFactory,
        ImageUploader $imageUploader,
        LoggerInterface $logger
    ) {
        $this->bannerFactory = $bannerFactory;
        $this->imageUploader = $imageUploader;
        $this->logger = $logger;
        parent::__construct($context);
    }

   public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($data) {
            // --- XỬ LÝ DỮ LIỆU ẢNH ---
            if (isset($data['image']) && is_array($data['image'])) {
                if (isset($data['image'][0]['name'])) {
                    $imageName = $data['image'][0]['name'];
                    
                    // Gán ngược lại vào data (String) để lưu DB
                    $data['image'] = $imageName;

                    try {
                        // Move file từ Tmp sang Chính thức
                        $this->imageUploader->moveFileFromTmp($imageName);
                    } catch (\Exception $e) {
                        $this->logger->critical('Image move error: ' . $e->getMessage());
                    }
                } else {
                    unset($data['image']);
                }
            } else {
                $data['image'] = null;
            }

            $model = $this->bannerFactory->create();

            if (!empty($data['banner_id'])) {
                $model->load($data['banner_id']);
                if (!$model->getId()) {
                    $this->messageManager->addErrorMessage(__('This banner no longer exists.'));
                    return $resultRedirect->setPath('*/*/');
                }
            } else {
                // Nếu là tạo mới (ID rỗng), xóa key banner_id đi để Magento tự tăng ID
                unset($data['banner_id']);
            }

            $model->setData($data);

            try {
                $model->save();
                $this->messageManager->addSuccessMessage(__('You saved the banner.'));
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }
        return $resultRedirect->setPath('*/*/');
    }
}