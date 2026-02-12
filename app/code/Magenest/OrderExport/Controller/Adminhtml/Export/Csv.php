<?php
declare(strict_types=1);

namespace Magenest\OrderExport\Controller\Adminhtml\Export;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Export orders to CSV
 */
class Csv extends Action
{
    const ADMIN_RESOURCE = 'Magenest_OrderExport::export';

    protected $fileFactory;
    protected $orderCollectionFactory;
    protected $logger;
    protected $productRepository;

    public function __construct(
        Context $context,
        FileFactory $fileFactory,
        OrderCollectionFactory $orderCollectionFactory,
        LoggerInterface $logger,
        ProductRepositoryInterface $productRepository
    ) {
        parent::__construct($context);
        $this->fileFactory = $fileFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->logger = $logger;
        $this->productRepository = $productRepository;
    }

    public function execute()
    {
        try {
            // Get filter parameters from request
            $fromDate = $this->getRequest()->getParam('from_date');
            $toDate = $this->getRequest()->getParam('to_date');
            $status = $this->getRequest()->getParam('status');

            $fileName = 'order_items_export_' . date('Ymd_His') . '.csv';
            $csvContent = $this->generateCsvContent($fromDate, $toDate, $status);

            return $this->fileFactory->create(
                $fileName,
                $csvContent,
                DirectoryList::VAR_DIR,
                'text/csv'
            );

        } catch (\Exception $e) {
            $this->logger->error('Export CSV Error: ' . $e->getMessage());
            $this->messageManager->addErrorMessage(__('An error occurred while exporting: %1', $e->getMessage()));
            return $this->_redirect('*/*/index');
        }
    }

    /**
     * Generate CSV content
     */
    private function generateCsvContent($fromDate = null, $toDate = null, $status = null)
    {
        $output = fopen('php://temp', 'w');

        // UTF-8 BOM for Excel
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // CSV Header
        fputcsv($output, [
            'ID Order',
            'Purchase Point',
            'Purchase Date',
            'Ref Code',
            'Product Name',
            'Product Qty',
            'Unit Price',
            'Grand Total (Base)'
        ]);

        // Build collection with filters
        $collection = $this->orderCollectionFactory->create()
            ->addFieldToSelect('*')
            ->setOrder('created_at', 'DESC');

        // Apply filters
        if ($fromDate) {
            $collection->addFieldToFilter('created_at', ['gteq' => $fromDate . ' 00:00:00']);
        }

        if ($toDate) {
            $collection->addFieldToFilter('created_at', ['lteq' => $toDate . ' 23:59:59']);
        }

        if ($status) {
            $collection->addFieldToFilter('status', $status);
        }

        $this->logger->info('Exporting ' . $collection->getSize() . ' orders');

        // Process each order
        foreach ($collection as $order) {
            $items = $order->getAllVisibleItems();

            foreach ($items as $item) {
                $rowData = [
                    $order->getIncrementId(),
                    $order->getStoreName(),
                    date('M:M d, Y', strtotime($order->getCreatedAt())),
                    $item->getSku(),
                    $item->getName(),
                    (int) $item->getQtyOrdered(),
                    number_format((float) $item->getPrice(), 0, '', ''),
                    number_format((float) $item->getRowTotal(), 0, '', '') . '₫'

                ];

                fputcsv($output, $rowData);
            }
        }

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return $csvContent;
    }
}