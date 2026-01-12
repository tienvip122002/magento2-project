<?php
namespace Magenest\CourseAttachment\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Framework\View\LayoutInterface;

class CourseAttachment extends AbstractModifier
{
    const GROUP_NAME = 'magenest_course_attachment_group';
    const TARGET_ATTRIBUTE_SET = 'Course';

    protected $arrayManager;
    protected $locator;
    protected $attributeSetRepository;
    protected $layout;

    public function __construct(
        ArrayManager $arrayManager,
        LocatorInterface $locator,
        AttributeSetRepositoryInterface $attributeSetRepository,
        LayoutInterface $layout
    ) {
        $this->arrayManager = $arrayManager;
        $this->locator = $locator;
        $this->attributeSetRepository = $attributeSetRepository;
        $this->layout = $layout;
    }

    public function modifyData(array $data)
    {
        // Don't inject data here, the Block handles it.
        return $data;
    }

    public function modifyMeta(array $meta)
    {
        // 1. Check Attribute Set
        if (!$this->isCourseAttributeSet()) {
            return $meta;
        }

        $meta = $this->arrayManager->set(
            self::GROUP_NAME,
            $meta,
            [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'label' => __('Course Attachments'),
                            'componentType' => 'fieldset',
                            'collapsible' => true,
                            'opened' => true,
                            'sortOrder' => 20,
                        ],
                    ],
                ],
                'children' => [
                    'course_attachments_content' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'autoRender' => true,
                                    'componentType' => 'container',
                                    'component' => 'Magento_Ui/js/form/components/html',
                                    'additionalClasses' => 'admin__fieldset-note',
                                    'content' => $this->getBlockHtml(), // Render Block Content
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );

        return $meta;
    }

    /**
     * Render the block content
     */
    protected function getBlockHtml()
    {
        $block = $this->layout->createBlock(
            \Magenest\CourseAttachment\Block\Adminhtml\Product\Edit\Tab\Attachments::class
        );
        return $block->toHtml();
    }

    /**
     * Helper: Check if current product is in 'Course' Attribute Set
     */
    protected function isCourseAttributeSet()
    {
        try {
            $product = $this->locator->getProduct();
            $setId = $product->getAttributeSetId();

            if (!$setId) {
                return false;
            }

            $attributeSet = $this->attributeSetRepository->get($setId);
            return $attributeSet->getAttributeSetName() === self::TARGET_ATTRIBUTE_SET;

        } catch (\Exception $e) {
            return false;
        }
    }
}
