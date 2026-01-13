<?php
namespace Magenest\Blog\Model\Blog;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Magenest\Blog\Model\ResourceModel\Blog\CollectionFactory;

class GridDataProvider extends AbstractDataProvider
{
    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        // Tạo collection từ factory
        $this->collection = $collectionFactory->create();
    }

    /**
     * Lấy dữ liệu để đổ ra Grid
     */
    public function getData()
    {
        if (!$this->getCollection()->isLoaded()) {
            $this->getCollection()->load();
        }
        
        // Format chuẩn cho UI Grid
        return [
            'totalRecords' => $this->getCollection()->getSize(),
            'items'        => $this->getCollection()->toArray()['items'],
        ];
    }
}