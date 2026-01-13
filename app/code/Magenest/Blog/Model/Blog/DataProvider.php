<?php
namespace Magenest\Blog\Model\Blog;

use Magenest\Blog\Model\ResourceModel\Blog\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;

class DataProvider extends AbstractDataProvider
{
    protected $loadedData;
    protected $dataPersistor;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $blogCollectionFactory,
        DataPersistorInterface $dataPersistor,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $blogCollectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        
        // Lấy dữ liệu từ DB để đổ vào form Edit
        $items = $this->collection->getItems();
        foreach ($items as $blog) {
            $this->loadedData[$blog->getId()] = $blog->getData();
        }

        // Lấy dữ liệu từ Session (trường hợp Save lỗi, reload lại form)
        $data = $this->dataPersistor->get('magenest_blog');
        if (!empty($data)) {
            $blog = $this->collection->getNewEmptyItem();
            $blog->setData($data);
            $this->loadedData[$blog->getId()] = $blog->getData();
            $this->dataPersistor->clear('magenest_blog');
        }

        return $this->loadedData;
    }
}