<?php
namespace Magenest\CourseAttachment\Block\Adminhtml\Product\Edit\Tab;

use Magento\Backend\Block\Template;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magenest\CourseAttachment\Model\ResourceModel\Attachment\CollectionFactory;

class Attachments extends Template
{
    protected $_template = 'Magenest_CourseAttachment::product/tab/attachments.phtml';

    /**
     * @var LocatorInterface
     */
    protected $locator;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @param Template\Context $context
     * @param LocatorInterface $locator
     * @param CollectionFactory $collectionFactory
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        LocatorInterface $locator,
        CollectionFactory $collectionFactory,
        array $data = []
    ) {
        $this->locator = $locator;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context, $data);
    }

    public function getAttachments()
    {
        $productId = $this->locator->getProduct()->getId();
        if (!$productId) {
            return [];
        }

        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('product_id', $productId);
        $collection->setOrder('sort_order', 'ASC');

        return $collection->getItems();
    }

    public function getProduct()
    {
        return $this->locator->getProduct();
    }
}
