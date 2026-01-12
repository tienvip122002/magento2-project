<?php
namespace Magenest\CustomerMangement\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Customer\Model\Customer;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;

class AddIsB2bAttribute implements DataPatchInterface
{
    private $moduleDataSetup;
    private $customerSetupFactory;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CustomerSetupFactory $customerSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->customerSetupFactory = $customerSetupFactory;
    }

    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $attributeCode = 'is_b2b';

        // 1. Thêm thuộc tính
        $customerSetup->addAttribute(
            Customer::ENTITY,
            $attributeCode,
            [
                'type'         => 'int',
                'label'        => 'Is B2B Account',
                'input'        => 'boolean',       // Kiểu Yes/No
                'source'       => Boolean::class,  // Source model cho Yes/No
                'required'     => false,
                'visible'      => true,
                'system'       => 0,
                'position'     => 150,
                'default'      => 0, // Mặc định là No
            ]
        );

        // 2. Gán vào form Admin (Để Admin sửa được)
        // Lưu ý: Không gán vào form Frontend để khách không tự sửa được
        $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $attributeCode);
        $attribute->setData(
            'used_in_forms',
            ['adminhtml_customer']
        );
        $attribute->save();

        // 3. Gán vào Attribute Set (Để hiển thị trong tab Account Information)
        $entityTypeId = Customer::ENTITY;
        $attributeSetId = $customerSetup->getDefaultAttributeSetId($entityTypeId);
        $attributeGroupId = $customerSetup->getDefaultAttributeGroupId($entityTypeId, $attributeSetId);

        $customerSetup->addAttributeToSet(
            $entityTypeId,
            $attributeSetId,
            $attributeGroupId,
            $attributeCode
        );

        $this->moduleDataSetup->endSetup();
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}