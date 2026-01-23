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

        // Lấy giá trị hiện tại đã được set vào object
        $objectValue = $object->getData($attributeCode);

        // Lấy dữ liệu từ POST
        $postData = $this->getRequest()->getPostValue();

        // Xác định xem có phải đang Save Customer ở Admin không
        $isCustomerPost = isset($postData['customer']) || isset($postData['customer_avatar']);

        $postValue = null;
        if (isset($postData['customer']) && array_key_exists($attributeCode, $postData['customer'])) {
            $postValue = $postData['customer'][$attributeCode];
        } elseif (isset($postData['customer_avatar']) && array_key_exists($attributeCode, $postData['customer_avatar'])) {
            $postValue = $postData['customer_avatar'][$attributeCode];
        }

        $finalValue = null;
        $valueProcessed = false;

        // DEBUG: Ghi log để kiểm tra dữ liệu thực tế
        if ($isCustomerPost) {
            \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Psr\Log\LoggerInterface::class)
                ->info('Avatar Post Value Debug: ' . json_encode($postValue));
        }

        // 1. Xử lý trường hợp lưu từ Admin Form (UI Component)
        if ($isCustomerPost) {
            $valueProcessed = true;

            // Case A: Xóa ảnh (Admin gửi lên null, mảng rỗng, hoặc flag delete)
            if (
                empty($postValue) || (is_array($postValue) && (
                    !empty($postValue['delete']) ||
                    (isset($postValue[0]['file']) && $postValue[0]['file'] === '[deleted]')
                ))
            ) {
                $this->deleteOldImage($object, $attributeCode);
                $finalValue = null;
            }
            // Case B: Upload mới hoặc giữ nguyên ảnh (UI gửi mảng chứa file info)
            elseif (is_array($postValue) && isset($postValue[0]['file']) && is_string($postValue[0]['file'])) {
                $file = $postValue[0]['file'];
                // Chuẩn hóa đường dẫn
                $file = ltrim($file, '/');
                if (strpos($file, 'customer/') === 0) {
                    $file = substr($file, strlen('customer'));
                } else {
                    $file = '/' . $file;
                }

                // Logic check xóa file cũ nếu là file mới
                $oldValue = $object->getOrigData($attributeCode);
                $isSameFile = false;

                $oldPath = null;
                if (is_array($oldValue) && isset($oldValue[0]['file'])) {
                    $oldPath = $oldValue[0]['file'];
                } elseif (is_array($oldValue) && isset($oldValue['file'])) {
                    $oldPath = $oldValue['file'];
                } elseif (is_string($oldValue)) {
                    $oldPath = $oldValue;
                }

                if ($oldPath) {
                    $cleanOldPath = ltrim($oldPath, '/');
                    if (strpos($cleanOldPath, 'customer/') === 0) {
                        $cleanOldPath = substr($cleanOldPath, strlen('customer'));
                    } else {
                        $cleanOldPath = '/' . $cleanOldPath;
                    }
                    if ($cleanOldPath === $file) {
                        $isSameFile = true;
                    }
                }

                if (!$isSameFile) {
                    $this->deleteOldImage($object, $attributeCode);
                }

                $finalValue = $file;
            }
            // Case C: Fallback an toàn, giữ nguyên giá trị cũ nếu format lạ
            else {
                $finalValue = $object->getOrigData($attributeCode);
            }
        }

        // 2. Xử lý trường hợp lưu từ Code khác / Frontend (Không phải Admin Post Form)
        if (!$valueProcessed) {
            // Xử lý string value
            if (is_string($objectValue) && !empty($objectValue)) {
                $file = ltrim($objectValue, '/');
                if (strpos($file, 'customer/') === 0) {
                    $file = substr($file, strlen('customer'));
                } else {
                    $file = '/' . $file;
                }

                // Logic check xóa file cũ (tương tự)
                $oldValue = $object->getOrigData($attributeCode);
                $isSameFile = false;

                $oldPath = null;
                if (is_array($oldValue) && isset($oldValue[0]['file'])) {
                    $oldPath = $oldValue[0]['file'];
                } elseif (is_string($oldValue)) {
                    $oldPath = $oldValue;
                }

                if ($oldPath) {
                    $cleanOldPath = ltrim($oldPath, '/');
                    if (strpos($cleanOldPath, 'customer/') === 0) {
                        $cleanOldPath = substr($cleanOldPath, strlen('customer'));
                    } else {
                        $cleanOldPath = '/' . $cleanOldPath;
                    }
                    if ($cleanOldPath === $file) {
                        $isSameFile = true;
                    }
                }

                if (!$isSameFile) {
                    $this->deleteOldImage($object, $attributeCode);
                }

                $finalValue = $file;
            }
            // Xử lý array value
            elseif (is_array($objectValue) && !empty($objectValue)) {
                if (isset($objectValue[0]['file']) && is_string($objectValue[0]['file'])) {
                    $file = ltrim($objectValue[0]['file'], '/');
                    if (strpos($file, 'customer/') === 0) {
                        $file = substr($file, strlen('customer'));
                    } else {
                        $file = '/' . $file;
                    }

                    // Đơn giản hóa: nếu set array mới, coi như file mới -> xóa cũ
                    $this->deleteOldImage($object, $attributeCode);

                    $finalValue = $file;
                }
            }
            // Mặc định giữ nguyên nếu không có thay đổi và không phải case xóa
            else {
                $finalValue = $object->getOrigData($attributeCode);
            }
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
        $path = null;

        if ($oldValue) {
            if (is_string($oldValue)) {
                $path = $oldValue;
            } elseif (is_array($oldValue)) {
                // Xử lý trường hợp dữ liệu load lên là array (do afterLoad)
                if (isset($oldValue[0]['file']) && is_string($oldValue[0]['file'])) {
                    $path = $oldValue[0]['file'];
                } elseif (isset($oldValue['file']) && is_string($oldValue['file'])) {
                    $path = $oldValue['file'];
                }
            }
        }

        if ($path) {
            $mediaDir = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);

            // Chuẩn hóa path: Bỏ 'customer/' nếu có vì DB lưu short path, nhưng array UI lại full path
            // Tuy nhiên, logic check file delete cần cẩn thận.
            // Nếu path bắt đầu bằng 'customer/', ta cần check xem file thực tế nằm đâu.

            // Cách an toàn: Check cả 2 đường dẫn
            $pathsToCheck = [];
            $cleanPath = ltrim($path, '/');

            $pathsToCheck[] = $cleanPath; // Path gốc

            if (strpos($cleanPath, 'customer/') === 0) {
                $pathsToCheck[] = substr($cleanPath, strlen('customer/')); // Path cắt customer
            }

            try {
                foreach ($pathsToCheck as $p) {
                    if ($mediaDir->isExist($p)) {
                        $mediaDir->delete($p);
                        // Chỉ xóa 1 lần là đủ
                        break;
                    }
                }
            } catch (\Exception $e) {
                // Log if needed
                \Magento\Framework\App\ObjectManager::getInstance()
                    ->get(\Psr\Log\LoggerInterface::class)
                    ->critical('Error deleting avatar: ' . $e->getMessage());
            }
        }
    }
}
