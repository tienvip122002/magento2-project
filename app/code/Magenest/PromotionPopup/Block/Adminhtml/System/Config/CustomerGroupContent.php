<?php
namespace Magenest\PromotionPopup\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class CustomerGroupContent extends Field
{
    protected $groupRepository;
    protected $searchCriteriaBuilder;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        GroupRepositoryInterface $groupRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        array $data = []
    ) {
        $this->groupRepository = $groupRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        parent::__construct($context, $data);
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        $html = '<table class="admin__control-table" id="' . $element->getHtmlId() . '">';
        $html .= '<thead><tr><th>Customer Group</th><th>Popup Content</th></tr></thead>';
        $html .= '<tbody>';

        // Get all customer groups
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $groups = $this->groupRepository->getList($searchCriteria)->getItems();

        // Get current saved values
        $values = $element->getValue();
        if (is_string($values)) {
            $values = @unserialize($values) ?: [];
        }
        if (!is_array($values)) {
            $values = [];
        }

        foreach ($groups as $group) {
            $groupId = $group->getId();
            $groupCode = $group->getCode();
            $content = isset($values[$groupId]) ? $this->escapeHtml($values[$groupId]) : '';

            $html .= '<tr>';
            $html .= '<td><strong>' . $this->escapeHtml($groupCode) . '</strong></td>';
            $html .= '<td>';
            $html .= '<textarea name="' . $element->getName() . '[' . $groupId . ']" ';
            $html .= 'rows="4" cols="50" class="textarea admin__control-textarea">';
            $html .= $content;
            $html .= '</textarea>';
            $html .= '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        return $html;
    }
}
