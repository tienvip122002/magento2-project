<?php
namespace Magenest\CourseAttachment\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magenest\CourseAttachment\Model\ResourceModel\Attachment\CollectionFactory as AttachmentCollectionFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class SendCourseAttachments implements ObserverInterface
{
    protected $transportBuilder;
    protected $inlineTranslation;
    protected $storeManager;
    protected $orderFactory;
    protected $checkoutSession;
    protected $attachmentCollectionFactory;
    protected $productRepository;
    protected $attributeSetRepository;
    protected $scopeConfig;

    const TARGET_ATTRIBUTE_SET = 'Course';

    public function __construct(
        TransportBuilder $transportBuilder,
        StateInterface $inlineTranslation,
        StoreManagerInterface $storeManager,
        OrderFactory $orderFactory,
        CheckoutSession $checkoutSession,
        AttachmentCollectionFactory $attachmentCollectionFactory,
        ProductRepository $productRepository,
        AttributeSetRepositoryInterface $attributeSetRepository,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->storeManager = $storeManager;
        $this->orderFactory = $orderFactory;
        $this->checkoutSession = $checkoutSession;
        $this->attachmentCollectionFactory = $attachmentCollectionFactory;
        $this->productRepository = $productRepository;
        $this->attributeSetRepository = $attributeSetRepository;
        $this->scopeConfig = $scopeConfig;
    }

    public function execute(Observer $observer)
    {
        // Get Order ID from Last Success Order
        $orderId = $this->checkoutSession->getLastOrderId();
        if (!$orderId) {
            return;
        }

        $order = $this->orderFactory->create()->load($orderId);
        if (!$order->getId()) {
            return;
        }

        $items = $order->getAllVisibleItems();
        $attachmentsData = [];

        foreach ($items as $item) {
            $product = $item->getProduct(); // Note: getProduct might not load all attributes

            // Re-load product to be sure about Attribute Set (optional, but safer)
            // Or use $item->getProductId() and check logic
            try {
                // Check if product is "Course"
                if (!$this->isCourseProduct($product)) {
                    continue;
                }

                // Get Attachments
                $attachments = $this->getAttachments($product->getId());
                if (!empty($attachments)) {
                    foreach ($attachments as $attachment) {
                        $attachmentsData[] = [
                            'product_name' => $item->getName(),
                            'label' => $attachment->getLabel(),
                            'type' => $attachment->getFileType(),
                            'path' => $attachment->getFilePath()
                        ];
                    }
                }

            } catch (\Exception $e) {
                // Log error
                continue;
            }
        }

        if (!empty($attachmentsData)) {
            $this->sendEmail($order, $attachmentsData);
        }
    }

    protected function isCourseProduct($product)
    {
        $setId = $product->getAttributeSetId();
        if (!$setId)
            return false;

        try {
            $attributeSet = $this->attributeSetRepository->get($setId);
            return $attributeSet->getAttributeSetName() === self::TARGET_ATTRIBUTE_SET;
        } catch (NoSuchEntityException $e) {
            return false;
        }
    }

    protected function getAttachments($productId)
    {
        $collection = $this->attachmentCollectionFactory->create();
        $collection->addFieldToFilter('product_id', $productId);
        $collection->setOrder('sort_order', 'ASC');
        return $collection->getItems();
    }

    protected function sendEmail($order, $attachmentsData)
    {
        $html = '';
        $mediaUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);

        foreach ($attachmentsData as $data) {
            $link = $data['path'];
            $linkText = 'View Link';
            $extraAttributes = 'target="_blank"';

            if ($data['type'] == 'file') {
                // If it's a relative path (from our upload), prepend media URL
                if (strpos($link, 'http') !== 0) {
                    $link = $mediaUrl . $link;
                }
                // Force download behavior
                $linkText = 'Download File';
                $extraAttributes = 'download';
            }

            $html .= '<tr>';
            $html .= '<td style="padding: 10px; border: 1px solid #ddd;">' . $data['product_name'] . '</td>';
            $html .= '<td style="padding: 10px; border: 1px solid #ddd;">' . $data['label'] . '</td>';
            $html .= '<td style="padding: 10px; border: 1px solid #ddd;">' . ($data['type'] == 'file' ? 'File' : 'Link') . '</td>';
            $html .= '<td style="padding: 10px; border: 1px solid #ddd;"><a href="' . $link . '" ' . $extraAttributes . '>' . $linkText . '</a></td>';
            $html .= '</tr>';
        }

        $templateVars = [
            'order' => $order,
            'customer_name' => $order->getCustomerName(),
            'attachments_html' => $html
        ];

        $sender = [
            'name' => $this->scopeConfig->getValue('trans_email/ident_general/name'),
            'email' => $this->scopeConfig->getValue('trans_email/ident_general/email'),
        ];

        try {
            $this->inlineTranslation->suspend();

            $transport = $this->transportBuilder
                ->setTemplateIdentifier('magenest_course_attachment_email_template')
                ->setTemplateOptions([
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $this->storeManager->getStore()->getId(),
                ])
                ->setTemplateVars($templateVars)
                ->setFromByScope($sender)
                ->addTo($order->getCustomerEmail(), $order->getCustomerName())
                ->getTransport();

            $transport->sendMessage();

            $this->inlineTranslation->resume();
        } catch (\Exception $e) {
            // Log error
            $logger = \Magento\Framework\App\ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class);
            $logger->error('Failed to send Course Attachment Email: ' . $e->getMessage());
        }
    }
}
