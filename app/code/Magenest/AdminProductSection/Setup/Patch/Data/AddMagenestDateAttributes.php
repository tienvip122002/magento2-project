<?php
declare(strict_types=1);


namespace Magenest\AdminProductSection\Setup\Patch\Data;


use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Output\ConsoleOutput; // Thêm cái này để in log ra màn hình


class AddMagenestDateAttributes implements DataPatchInterface
{
   private $output;


   public function __construct(
       private readonly ModuleDataSetupInterface $moduleDataSetup,
       private readonly EavSetupFactory $eavSetupFactory,
       private readonly EavConfig $eavConfig,
       private readonly AttributeSetFactory $attributeSetFactory
   ) {
       $this->output = new ConsoleOutput();
   }


   public function apply(): void
   {
       $this->log("--> BẮT ĐẦU CHẠY PATCH...");
       $this->moduleDataSetup->getConnection()->startSetup();
      
       try {
           $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
           $entityType = Product::ENTITY;
           $entityTypeId = (int)$this->eavConfig->getEntityType($entityType)->getEntityTypeId();
          
           $this->log("1. Entity Type ID tìm thấy: " . $entityTypeId);


           // --- KIỂM TRA DEFAULT SET ID ---
           $defaultSetId = $eavSetup->getDefaultAttributeSetId($entityTypeId);
           $this->log("2. Default Attribute Set ID tìm thấy: " . ($defaultSetId ? $defaultSetId : "NULL/0"));


           if (!$defaultSetId) {
               throw new LocalizedException(__(' Không tìm thấy Default Attribute Set ID. DB của bạn đang bị thiếu dữ liệu gốc!'));
           }


           // --- TẠO ATTRIBUTE (Logic cũ) ---
           $attrs = ['magenest_from_date', 'magenest_to_date'];
           // (Đoạn tạo attribute giữ nguyên, bỏ qua log cho gọn)
          
           // --- TẠO SET COURSE ---
           $courseSetName = 'Course';
           $courseSetId = $this->getAttributeSetIdByName($entityTypeId, $courseSetName);
          
           $this->log("3. Kiểm tra Set 'Course' trong DB: " . ($courseSetId ? "Đã có (ID: $courseSetId)" : "Chưa có"));


           if (!$courseSetId) {
               $this->log("--> Đang tiến hành tạo mới Attribute Set 'Course'...");
              
               $attributeSet = $this->attributeSetFactory->create();
               $attributeSet->setEntityTypeId($entityTypeId);
               $attributeSet->setAttributeSetName($courseSetName);
               $attributeSet->setSortOrder(200);
               $attributeSet->save();


               // Lấy ID ngay sau khi save model
               $newId = $attributeSet->getId();
               $this->log("--> Save Model xong. ID tạm thời: " . $newId);


               $attributeSet->initFromSkeleton($defaultSetId);
               $attributeSet->save();
               $this->log("--> Init Skeleton từ Default ($defaultSetId) xong.");
              
               // Lấy lại ID từ DB để chắc chắn
               $courseSetId = $this->getAttributeSetIdByName($entityTypeId, $courseSetName);
               $this->log("4. ID của Course Set sau khi tạo xong: " . ($courseSetId ? $courseSetId : "VẪN NULL!!!"));
           }


           if (!$courseSetId) {
               throw new LocalizedException(__('Lỗi: Không thể lấy được ID của Course Set sau khi tạo.'));
           }


           // --- TẠO GROUP ---
           $groupName = 'Magenest Course Info';
           $groupId = $eavSetup->getAttributeGroupId($entityTypeId, $courseSetId, $groupName);
           $this->log("5. Group ID ($groupName): " . ($groupId ? $groupId : "Chưa có, đang tạo..."));


           if (!$groupId) {
               $eavSetup->addAttributeGroup($entityTypeId, $courseSetId, $groupName, 10);
               $groupId = $eavSetup->getAttributeGroupId($entityTypeId, $courseSetId, $groupName);
               $this->log("--> Group ID mới tạo: " . $groupId);
           }


           // --- GÁN ATTRIBUTE ---
           $this->log("6. Bắt đầu gán Attribute vào Set ID: $courseSetId, Group ID: $groupId");
          
           foreach ($attrs as $code) {
               $attributeId = $eavSetup->getAttributeId($entityTypeId, $code);
               $this->log("   - Gán attribute '$code' (ID: $attributeId)...");
              
               if ($attributeId && $courseSetId && $groupId) {
                   // Dùng try-catch riêng cho từng cái
                   try {
                       $eavSetup->addAttributeToGroup($entityTypeId, $courseSetId, $groupId, $attributeId);
                       $this->log("     -> Thành công.");
                   } catch (\Exception $e) {
                       $this->log("     -> LỖI GÁN: " . $e->getMessage());
                   }
               } else {
                    $this->log("     -> SKIPPED: Thiếu ID (Set, Group hoặc Attr)");
               }
           }


       } catch (\Exception $e) {
           $this->log("!!! CÓ LỖI XẢY RA TRONG CATCH: " . $e->getMessage());
           throw $e;
       }


       $this->moduleDataSetup->getConnection()->endSetup();
       $this->log("--> KẾT THÚC PATCH.");
   }


   private function getAttributeSetIdByName($entityTypeId, $name)
   {
       $connection = $this->moduleDataSetup->getConnection();
       $select = $connection->select()
           ->from($this->moduleDataSetup->getTable('eav_attribute_set'), 'attribute_set_id')
           ->where('entity_type_id = ?', $entityTypeId)
           ->where('attribute_set_name = ?', $name);
       return $connection->fetchOne($select);
   }
  
   private function log($msg) {
       $this->output->writeln('<info>' . $msg . '</info>');
   }


   public static function getDependencies(): array { return []; }
   public function getAliases(): array { return []; }
}