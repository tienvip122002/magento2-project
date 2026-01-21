<?php
declare(strict_types=1);

namespace Magenest\CustomerAvatar\Controller\Avatar;

use Magento\Customer\Controller\AbstractAccount;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\Filesystem;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\UrlInterface;

/**
 * Frontend Avatar Upload Controller
 */
class Upload extends AbstractAccount
{
    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var UploaderFactory
     */
    protected $uploaderFactory;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param UploaderFactory $uploaderFactory
     * @param Filesystem $filesystem
     * @param StoreManagerInterface $storeManager
     * @param Session $customerSession
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        UploaderFactory $uploaderFactory,
        Filesystem $filesystem,
        StoreManagerInterface $storeManager,
        Session $customerSession
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->uploaderFactory = $uploaderFactory;
        $this->filesystem = $filesystem;
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
    }

    /**
     * Upload avatar file
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();

        // Check if customer is logged in
        if (!$this->customerSession->isLoggedIn()) {
            return $result->setData([
                'error' => __('Please login to upload avatar.'),
                'errorcode' => 401
            ]);
        }

        try {
            $mediaDir = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);

            // Get file from request - support both 'avatar' and nested format
            $uploader = null;

            // Try simple 'avatar' first
            if (isset($_FILES['avatar']) && !empty($_FILES['avatar']['tmp_name'])) {
                $uploader = $this->uploaderFactory->create(['fileId' => 'avatar']);
            }
            // Try nested format customer[avatar]
            elseif (isset($_FILES['customer']['tmp_name']['avatar']) && !empty($_FILES['customer']['tmp_name']['avatar'])) {
                $file = [
                    'name' => $_FILES['customer']['name']['avatar'],
                    'type' => $_FILES['customer']['type']['avatar'],
                    'tmp_name' => $_FILES['customer']['tmp_name']['avatar'],
                    'error' => $_FILES['customer']['error']['avatar'],
                    'size' => $_FILES['customer']['size']['avatar'],
                ];
                $uploader = $this->uploaderFactory->create(['fileId' => $file]);
            }

            if (!$uploader) {
                throw new \RuntimeException('No file uploaded.');
            }

            $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png']);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(false);

            $path = 'customer/avatar';
            $saved = $uploader->save($mediaDir->getAbsolutePath($path));

            $fileName = basename($saved['file']);
            $relativePath = $path . '/' . $fileName;

            $baseMediaUrl = $this->storeManager->getStore()
                ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);

            return $result->setData([
                'name' => $saved['name'],
                'file' => $relativePath,
                'url' => rtrim($baseMediaUrl, '/') . '/' . $relativePath,
                'size' => $saved['size'],
                'type' => $saved['type'],
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'error' => $e->getMessage(),
                'errorcode' => $e->getCode()
            ]);
        }
    }
}
