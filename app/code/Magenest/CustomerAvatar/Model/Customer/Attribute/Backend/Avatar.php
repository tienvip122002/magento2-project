<?php

declare(strict_types=1);

namespace Magenest\CustomerAvatar\Model\Customer\Attribute\Backend;

use Magento\Eav\Model\Entity\Attribute\Backend\AbstractBackend;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;

class Avatar extends AbstractBackend
{
    private $storeManager;
    private $filesystem;

    public function __construct(
        StoreManagerInterface $storeManager,
        Filesystem $filesystem
    ) {
        $this->storeManager = $storeManager;
        $this->filesystem = $filesystem;
    }

    /**
     * Get Request from ObjectManager
     */
    private function getRequest()
    {
        return ObjectManager::getInstance()->get(\Magento\Framework\App\RequestInterface::class);
    }

    /**
     * Convert string path -> array for preview when loading customer
     */
    public function afterLoad($object)
    {
        $attributeCode = $this->getAttribute()->getAttributeCode();
        $value = $object->getData($attributeCode);

        if (is_string($value) && $value) {
            // Chuẩn hóa đường dẫn: Xóa dấu / ở đầu và cuối
            $path = trim($value, '/');
            $mediaDir = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);

            // Xác định đường dẫn thực tế của file trong media folder
            // DB có thể lưu: "/avatar/file.jpg" hoặc "avatar/file.jpg" hoặc "customer/avatar/file.jpg"
            $finalPath = null;

            if ($mediaDir->isExist($path)) {
                // Nếu đường dẫn trong DB tồn tại trực tiếp (ví dụ: customer/avatar/a.jpg)
                $finalPath = $path;
            } elseif ($mediaDir->isExist('customer/' . $path)) {
                // Nếu DB lưu avatar/a.jpg -> file thực tế ở customer/avatar/a.jpg
                $finalPath = 'customer/' . $path;
            } elseif ($mediaDir->isExist('customer' . $value)) {
                // Nếu DB lưu /avatar/a.jpg (có dấu / đầu) -> file ở customer/avatar/a.jpg
                $finalPath = 'customer' . $value;
            } else {
                // File không tồn tại -> Bỏ qua
                return $this;
            }

            // Tính toán URL và Size từ $finalPath chuẩn
            $baseMediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
            $url = rtrim($baseMediaUrl, '/') . '/' . ltrim($finalPath, '/');

            $size = 0;
            $type = 'unknown';

            try {
                $stat = $mediaDir->stat($finalPath);
                $size = $stat['size'];
                $type = 'image';
            } catch (\Exception $e) {
                // Không lấy được stat thì thôi
                $size = 0;
            }

            // QUAN TRỌNG: Trả về đường dẫn đầy đủ "customer/avatar/file.jpg" cho field file
            // Khi beforeSave xử lý, nó sẽ cắt bỏ "customer" và lưu "/avatar/file.jpg"
            $previewData = [
                [
                    'name' => basename($finalPath),
                    'url' => $url,
                    'file' => $finalPath, // customer/avatar/file.jpg
                    'size' => $size,
                    'type' => $type
                ]
            ];

            $object->setData($attributeCode, $previewData);
        }

        return $this;
    }

    /**
     * Convert array -> string path before save
     */
    public function beforeSave($object)
    {
        $attributeCode = $this->getAttribute()->getAttributeCode();

        // Lấy giá trị hiện tại đã được set vào object (từ setCustomAttribute hoặc setData)
        $objectValue = $object->getData($attributeCode);

        // Lấy dữ liệu từ POST (do UI Component gửi lên) - chỉ cho admin
        $postData = $this->getRequest()->getPostValue();
        $postValue = null;

        if (isset($postData['customer'][$attributeCode])) {
            $postValue = $postData['customer'][$attributeCode];
        } elseif (isset($postData['customer_avatar'][$attributeCode])) {
            $postValue = $postData['customer_avatar'][$attributeCode];
        }

        $finalValue = null;

        // Ưu tiên xử lý POST data từ admin UI (array format)
        if (is_array($postValue) && !empty($postValue)) {
            // Trường hợp 1: Xóa ảnh
            if (!empty($postValue['delete']) || (isset($postValue[0]['file']) && $postValue[0]['file'] === '[deleted]')) {
                $this->deleteOldImage($object, $attributeCode);
                $finalValue = null;
            }
            // Trường hợp 2: Upload ảnh mới từ admin
            elseif (isset($postValue[0]['file']) && is_string($postValue[0]['file'])) {
                $file = $postValue[0]['file'];

                // Chuẩn hóa đường dẫn
                $file = ltrim($file, '/');

                // Cắt bỏ "customer/" prefix nếu có (do Magento tự thêm khi save)
                if (strpos($file, 'customer/') === 0) {
                    $file = substr($file, strlen('customer'));
                } else {
                    $file = '/' . $file;
                }

                $finalValue = $file;
            }
            // Trường hợp 3: Không thay đổi gì (giữ nguyên ảnh cũ)
            else {
                $finalValue = $object->getOrigData($attributeCode);
            }
        }
        // Xử lý string value (từ frontend controller hoặc direct set)
        elseif (is_string($objectValue) && !empty($objectValue)) {
            $file = ltrim($objectValue, '/');

            // Chuẩn hóa path cho database
            if (strpos($file, 'customer/') === 0) {
                $file = substr($file, strlen('customer'));
            } else {
                $file = '/' . $file;
            }

            $finalValue = $file;
        }
        // Xử lý array value được set trực tiếp vào object
        elseif (is_array($objectValue) && !empty($objectValue)) {
            if (isset($objectValue[0]['file']) && is_string($objectValue[0]['file'])) {
                $file = ltrim($objectValue[0]['file'], '/');

                if (strpos($file, 'customer/') === 0) {
                    $file = substr($file, strlen('customer'));
                } else {
                    $file = '/' . $file;
                }

                $finalValue = $file;
            }
        }
        // Nếu không có giá trị mới, giữ nguyên giá trị cũ
        elseif ($objectValue === null && $postValue === null) {
            $finalValue = $object->getOrigData($attributeCode);
        }

        // Set giá trị chuẩn vào model để lưu xuống DB
        $object->setData($attributeCode, $finalValue);

        return parent::beforeSave($object);
    }

    /**
     * Delete old image file
     */
    private function deleteOldImage($object, $attributeCode)
    {
        $oldValue = $object->getOrigData($attributeCode);

        if ($oldValue && is_string($oldValue)) {
            $mediaDir = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            $path = ltrim($oldValue, '/'); // Normalize path

            try {
                if ($mediaDir->isExist($path)) {
                    $mediaDir->delete($path);
                }
            } catch (\Exception $e) {
                // Log if needed
            }
        }
    }
}
