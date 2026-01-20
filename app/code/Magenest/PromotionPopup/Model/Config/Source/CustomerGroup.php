<?php
namespace Magenest\PromotionPopup\Model\Config\Source;

use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Data\OptionSourceInterface;

class CustomerGroup implements OptionSourceInterface
{
    protected $groupRepository;
    protected $searchCriteriaBuilder;

    public function __construct(
        GroupRepositoryInterface $groupRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->groupRepository = $groupRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    public function toOptionArray()
    {
        $options = [];
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $groups = $this->groupRepository->getList($searchCriteria)->getItems();

        foreach ($groups as $group) {
            $options[] = [
                'value' => $group->getId(),
                'label' => $group->getCode()
            ];
        }

        return $options;
    }
}
