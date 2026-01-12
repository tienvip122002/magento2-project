<?php
namespace Magenest\CustomerMangement\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Customer\Model\Customer; 
use Magenest\CustomerMangement\Model\Config\Source\VnRegion; // Import Source Model vừa tạo

class AddVnRegionAttribute implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var CustomerSetupFactory
     */
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

        // Tạo đối tượng CustomerSetup
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);

        // QUAN TRỌNG: Entity Type là 'customer_address' (Không phải 'customer')
        $entityType = 'customer_address'; 
        $attributeCode = 'vn_region';

        // 1. Thêm Attribute
        $customerSetup->addAttribute(
            $entityType,
            $attributeCode,
            [
                'type'         => 'int',           // Lưu value là 1, 2, 3 nên dùng int
                'label'        => 'Vietnam Region',
                'input'        => 'select',        // Kiểu hiển thị Dropdown
                'source'       => VnRegion::class, // Trỏ đến file Source Model bạn vừa tạo ở Bước 1
                
                'required'     => false,
                'visible'      => true,
                'user_defined' => true,
                'position'     => 150,             // Vị trí hiển thị
                'system'       => 0,
            ]
        );

        // 2. Gán Attribute vào các Form Address
        $attribute = $customerSetup->getEavConfig()->getAttribute($entityType, $attributeCode);
        
        $attribute->setData(
            'used_in_forms',
            [
                'adminhtml_customer_address', // Form sửa địa chỉ trong Admin
                'customer_address_edit',      // Form sửa địa chỉ ngoài Frontend (My Account)
                'customer_register_address'   // Form đăng ký tài khoản (nếu có bật field địa chỉ)
            ]
        );
        $attribute->save();

        // 3. Gán vào Attribute Set (Để chắc chắn hiện trong Admin)
        // Lấy Attribute Set mặc định của customer_address
        $attributeSetId = $customerSetup->getDefaultAttributeSetId($entityType);
        $attributeGroupId = $customerSetup->getDefaultAttributeGroupId($entityType, $attributeSetId);

        $customerSetup->addAttributeToSet(
            $entityType,
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