<?php
declare(strict_types=1);

namespace Magenest\AdminProductSection\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class UpdateDateAttributeGrid implements DataPatchInterface
{
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly EavSetupFactory $eavSetupFactory
    ) {
    }

    public function apply(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $entityType = Product::ENTITY;
        $attributes = ['magenest_from_date', 'magenest_to_date'];

        foreach ($attributes as $attributeCode) {
            if ($eavSetup->getAttributeId($entityType, $attributeCode)) {
                $eavSetup->updateAttribute($entityType, $attributeCode, 'is_used_in_grid', 1);
                $eavSetup->updateAttribute($entityType, $attributeCode, 'is_visible_in_grid', 1);
                $eavSetup->updateAttribute($entityType, $attributeCode, 'is_filterable_in_grid', 1);
            }
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    public static function getDependencies(): array
    {
        return [
            UpdateDateAttributeBackend::class
        ];
    }

    public function getAliases(): array
    {
        return [];
    }
}
