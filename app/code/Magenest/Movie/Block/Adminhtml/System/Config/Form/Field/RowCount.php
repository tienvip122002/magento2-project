<?php
declare(strict_types=1);

namespace Magenest\Movie\Block\Adminhtml\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\Form\Element\AbstractElement;

class RowCount extends Field
{
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        private ResourceConnection $resource,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    protected function _getElementHtml(AbstractElement $element): string
    {
        $fieldConfig = $element->getFieldConfig();

        // sử dụng table ở comment chuyền vào đkiểm tra dòng
        // còn cách nữa là inject table từ di.xml sẽ chuẩn hơn
        //còn cách không dùng ResourceConnection là dùng collectionfactory chuyền từ resourcemodel thì truy vấn cả dòng sẽ nặng sqk hơn, ko nên dùng

        $rawComment = $fieldConfig['comment'] ?? '';

        $comment = '';
        if (is_array($rawComment)) {
            $parts = [];
            foreach ($rawComment as $c) {
                if (is_array($c)) {
                    $c = $c['#text'] ?? '';
                }
                if (is_string($c) && $c !== '') {
                    $parts[] = $c;
                }
            }
            $comment = implode(' ', $parts);
        } else {
            $comment = (string)$rawComment;
        }

        $table = '';
        if (preg_match('/<!--\s*table\s*:\s*([a-z0-9_]+)\s*-->/', $comment, $m)) {
            $table = $m[1];
        }

        $count = 0;
        if ($table !== '') {
            $conn = $this->resource->getConnection();
            $tableName = $this->resource->getTableName($table);
            $count = (int)$conn->fetchOne("SELECT COUNT(*) FROM {$tableName}");
        }

        // Set value + make it readonly/disabled so it won't be saved/edited
        $element->setValue((string)$count);
        $element->setReadonly(true, true);
        $element->setDisabled('disabled');

        return $element->getElementHtml();
    }
}
