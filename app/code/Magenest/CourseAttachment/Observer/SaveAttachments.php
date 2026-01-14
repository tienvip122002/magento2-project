<?php
namespace Magenest\CourseAttachment\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;
use Magenest\CourseAttachment\Model\AttachmentFactory;
use Magenest\CourseAttachment\Model\ResourceModel\Attachment\CollectionFactory;
use Psr\Log\LoggerInterface;

class SaveAttachments implements ObserverInterface
{
    /**
     * Tên key chứa dữ liệu gửi lên (phải khớp với form name trong phtml)
     */
    const DATA_SCOPE = 'course_attachments_list';

    /**
     * [THÊM] Cờ đánh dấu: form attachments đã submit
     * Dùng để tránh trường hợp không POST field => Observer hiểu nhầm và xóa nhầm
     */
    const SUBMIT_FLAG = 'course_attachments_list_submitted';

    protected $request;
    protected $attachmentFactory;
    protected $collectionFactory;
    protected $logger;

    public function __construct(
        RequestInterface $request,
        AttachmentFactory $attachmentFactory,
        CollectionFactory $collectionFactory,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->attachmentFactory = $attachmentFactory;
        $this->collectionFactory = $collectionFactory;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        // 1. Lấy Product ID vừa lưu
        $product = $observer->getEvent()->getProduct();
        $productId = (int) $product->getId();

        if ($productId <= 0) {
            return;
        }

        // 2. Lấy dữ liệu từ Form gửi lên
        $formData = (array) $this->request->getPostValue();
        $productData = (array) ($formData['product'] ?? []);


        /**
         * [THÊM] 2A) Chỉ xử lý khi attachments.phtml thực sự submit (có hidden flag)
         * - Không có flag => không đụng gì (tránh xóa nhầm khi save từ nơi khác)
         */
        $submitted = !empty($productData[self::SUBMIT_FLAG]);
        if (!$submitted) {
            $this->logger->info('Magenest_CourseAttachment: Submit flag NOT found, skip processing.');
            return;
        }

        /**
         * [SỬA] 2B) Lấy incoming data
         * - Trước đây: nếu không có key DATA_SCOPE thì return luôn
         * - Bây giờ: nếu submit flag có mà incoming list rỗng => delete all
         */
        $incomingData = (array) ($productData[self::DATA_SCOPE] ?? []);

        /**
         * [THÊM] 2C) Nếu incoming list trống / sau lọc không còn row hợp lệ => xóa hết
         * Đây là “logic xóa trước” 
         */
        if (empty($incomingData) || $this->isIncomingEffectivelyEmpty($incomingData)) {
            $this->logger->info('Magenest_CourseAttachment: Incoming list empty => delete ALL attachments by product_id', [
                'product_id' => $productId
            ]);

            $this->deleteAllByProductId($productId);
            return;
        }

        // 3. XỬ LÝ LOGIC: XÓA - THÊM - SỬA

        // BƯỚC 3A: Xóa các dòng đã bị Admin xóa trên UI
        // Lấy danh sách ID đang có trong DB
        $existingCollection = $this->collectionFactory->create();
        $existingCollection->addFieldToFilter('product_id', $productId);

        $existingIds = [];
        foreach ($existingCollection as $item) {
            $existingIds[] = $item->getId();
        }

        // Lấy danh sách ID từ Form gửi lên (những cái còn giữ lại)
        $incomingIds = [];
        foreach ($incomingData as $row) {
            if (isset($row['entity_id']) && !empty($row['entity_id'])) {
                $incomingIds[] = $row['entity_id'];
            }
        }

        // Tìm những ID có trong DB mà không có trong Form -> Xóa đi
        $idsToDelete = array_diff($existingIds, $incomingIds);
        if (!empty($idsToDelete)) {
            $this->deleteAttachments($idsToDelete);
        }


        // BƯỚC 3B: Lưu/update
        foreach ($incomingData as $key => $row) {
            // Bỏ qua dòng template rỗng (nếu có)
            if (isset($row['delete']) && (int) $row['delete'] === 1) {
                continue;
            }

            // Skip nếu label rỗng
            if (empty($row['label'])) {
                $this->logger->warning("Magenest_CourseAttachment: Row $key skipped - No Label");
                continue;
            }

            // Khởi tạo model
            $model = $this->attachmentFactory->create();

            // Nếu có ID -> Load cũ để Update
            if (isset($row['entity_id']) && !empty($row['entity_id'])) {
                $model->load($row['entity_id']);
            }

            // Gán dữ liệu
            $model->setData('product_id', $productId);
            $model->setData('label', $row['label']);
            $model->setData('file_type', $row['file_type'] ?? 'file');
            $model->setData('file_path', $row['file_path'] ?? '');
            $model->setData('sort_order', (int) ($row['sort_order'] ?? 0));

            // Lưu vào DB
            try {
                $model->save();
                $this->logger->info("Magenest_CourseAttachment: Saved Row $key - ID: " . $model->getId());
            } catch (\Exception $e) {
                $this->logger->error("Magenest_CourseAttachment: Save Error Row $key: " . $e->getMessage());
            }
        }
    }

    /**
     * Hàm phụ để xóa nhiều dòng cùng lúc (giữ nguyên của ông)
     */
    private function deleteAttachments($ids)
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('entity_id', ['in' => $ids]);
        foreach ($collection as $item) {
            $item->delete();
        }
    }

    /**
     * [THÊM] Xóa toàn bộ attachments theo product_id (delete all)
     */
    private function deleteAllByProductId(int $productId): void
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('product_id', $productId);
        foreach ($collection as $item) {
            $item->delete();
        }
    }

    /**
     * [THÊM] Kiểm tra incoming list “thực sự rỗng” sau khi lọc
     * - Nếu toàn row label rỗng / row delete=1 => coi như rỗng
     */
    private function isIncomingEffectivelyEmpty(array $incomingData): bool
    {
        foreach ($incomingData as $row) {
            if (!is_array($row)) {
                continue;
            }

            if (!empty($row['delete']) && (int) $row['delete'] === 1) {
                continue;
            }

            if (isset($row['label']) && trim((string) $row['label']) !== '') {
                return false; // có ít nhất 1 row hợp lệ
            }
        }
        return true;
    }
}
