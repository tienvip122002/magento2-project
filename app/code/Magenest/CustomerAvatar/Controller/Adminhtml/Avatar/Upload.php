<?php
declare(strict_types=1);

namespace Magenest\CustomerAvatar\Controller\Adminhtml\Avatar;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\Filesystem;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\UrlInterface;

class Upload extends Action
{
    protected $jsonFactory;
    protected $uploaderFactory;
    protected $filesystem;
    protected $storeManager;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        UploaderFactory $uploaderFactory,
        Filesystem $filesystem,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->uploaderFactory = $uploaderFactory;
        $this->filesystem = $filesystem;
        $this->storeManager = $storeManager;
    }

    /**
     * Upload file controller action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();

        try {
            $mediaDir = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);

            // UI gửi param_name, ảnh ông chụp là: customer_avatar[avatar]
            $paramName = (string) $this->getRequest()->getParam('param_name', 'avatar');

            // Nếu flat: avatar
            if (strpos($paramName, '[') === false) {
                $uploader = $this->uploaderFactory->create(['fileId' => $paramName]);
            } else {
                // Nested: customer_avatar[avatar]
                if (!preg_match('/^([^\[]+)\[([^\]]+)\]$/', $paramName, $m)) {
                    throw new \RuntimeException('Invalid param_name: ' . $paramName);
                }

                $root = $m[1];   // customer_avatar
                $child = $m[2];  // avatar

                // Safe logging
                $logMsg = "Upload Debug:\nParam: $paramName\nRoot: $root\nChild: $child\nFILES: " . print_r($_FILES, true) . "\n";
                file_put_contents(BP . '/var/log/magenest_avatar_upload.log', $logMsg, FILE_APPEND);

                // Use $_FILES directly for robustness with nested arrays
                if (isset($_FILES[$root]['name'][$child])) {
                    $file = [
                        'name' => $_FILES[$root]['name'][$child],
                        'type' => $_FILES[$root]['type'][$child],
                        'tmp_name' => $_FILES[$root]['tmp_name'][$child],
                        'error' => $_FILES[$root]['error'][$child],
                        'size' => $_FILES[$root]['size'][$child],
                    ];
                } else {
                    // Fallback to request method if $_FILES is structured differently (unlikely but possible)
                    $files = $this->getRequest()->getFiles($root);
                    if (!$files || !isset($files['tmp_name'][$child])) {
                        throw new \RuntimeException('No file uploaded for ' . $paramName);
                    }
                    $file = [
                        'name' => $files['name'][$child] ?? null,
                        'type' => $files['type'][$child] ?? null,
                        'tmp_name' => $files['tmp_name'][$child] ?? null,
                        'error' => $files['error'][$child] ?? null,
                        'size' => $files['size'][$child] ?? null,
                    ];
                }

                if (!isset($file['tmp_name']) || !is_string($file['tmp_name'])) {
                    throw new \RuntimeException('Invalid tmp_name type for ' . $paramName);
                }

                // Create uploader
                $uploader = $this->uploaderFactory->create(['fileId' => $file]);
            }

            $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png']);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(false);


            $path = 'customer/avatar';
            
            // Lưu file
            $saved = $uploader->save($mediaDir->getAbsolutePath($path));

            // Lấy tên file đã lưu (quan trọng: chỉ lấy tên file, không lấy đường dẫn)
            $fileName = $saved['file']; 
            
            // Fix lỗi: Một số uploader trả về full path, ta chỉ lấy basename để an toàn
            $fileName = basename($fileName);

            // Tạo đường dẫn tương đối để lưu vào DB
            // Kết quả sẽ là: customer/avatar/ten_file.jpg
            $relativePath = $path . '/' . $fileName;

            $baseMediaUrl = $this->storeManager->getStore()
                ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);

            return $result->setData([
                'name' => $saved['name'],
                'file' => $relativePath, // UI nhận giá trị này gửi lại cho Backend Model
                // Fix lỗi url: dùng rtrim để tránh double slash
                'url' => rtrim($baseMediaUrl, '/') . '/' . $relativePath,
                'size' => $saved['size'],
                'type' => $saved['type'],
                'cookie' => [
                    'name' => $this->_getSession()->getName(),
                    'value' => $this->_getSession()->getSessionId(),
                    'lifetime' => $this->_getSession()->getCookieLifetime(),
                    'path' => $this->_getSession()->getCookiePath(),
                    'domain' => $this->_getSession()->getCookieDomain(),
                ]
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'error' => $e->getMessage(),
                'errorcode' => $e->getCode()
            ]);
        }
    }

}