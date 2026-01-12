<?php
namespace Magenest\CustomerMangement\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Customer\Model\Customer;

class AddCustomerTelephoneAttributesss implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var CustomerSetupFactory
     */
    private $customerSetupFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CustomerSetupFactory $customerSetupFactory
     */
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

        // Tên mã của attribute
        $attributeCode = 'customer_telephone';

        // 1. Thêm Attribute vào Database
        // Dùng addAttribute sẽ tự động update nếu attribute đã tồn tại
        $customerSetup->addAttribute(
            Customer::ENTITY, 
            $attributeCode,
            [
                'type'         => 'varchar',       
                'label'        => 'Phone Number',  
                'input'        => 'text',          
                'required'     => false,           
                'visible'      => true,
                'user_defined' => true,            
                'position'     => 100,             
                'system'       => 0,     
                'backend'      => \Magenest\CustomerMangement\Model\Attribute\Backend\Telephone::class,          
                
                // Cấu hình hiển thị trong Admin Grid
                'is_used_in_grid'       => true,
                'is_visible_in_grid'    => true,
                'is_filterable_in_grid' => true,
                'is_searchable_in_grid' => true,
            ]
        );

        // 2. Gán Attribute vào các Form
        $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $attributeCode);
        
        $attribute->setData(
            'used_in_forms',
            [
                'adminhtml_customer',       
                'customer_account_create',  
                'customer_account_edit',    
                'adminhtml_checkout',       
            ]
        );
        $attribute->save();

        // --- PHẦN MỚI THÊM ĐỂ FIX LỖI KHÔNG LƯU DB ---
        // 3. Gán Attribute vào Attribute Set & Group (Quan trọng!)
        
        $entityTypeId = Customer::ENTITY; // ID = 1
        
        // Lấy Attribute Set mặc định (thường là ID 1)
        $attributeSetId = $customerSetup->getDefaultAttributeSetId($entityTypeId);
        
        // Lấy Attribute Group mặc định (thường là General)
        $attributeGroupId = $customerSetup->getDefaultAttributeGroupId($entityTypeId, $attributeSetId);

        // Thực hiện gán
        $customerSetup->addAttributeToSet(
            $entityTypeId,
            $attributeSetId,
            $attributeGroupId,
            $attributeCode
        );
        // ----------------------------------------------

        $this->moduleDataSetup->endSetup();
    }

    /**
     * Các patch cần chạy trước
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * Tên định danh phiên bản
     */
    public function getAliases()
    {
        return [];
    }
}