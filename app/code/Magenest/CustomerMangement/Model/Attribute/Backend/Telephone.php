<?php
namespace Magenest\CustomerMangement\Model\Attribute\Backend;

use Magento\Eav\Model\Entity\Attribute\Backend\AbstractBackend;
use Magento\Framework\Exception\LocalizedException;

class Telephone extends AbstractBackend
{
    /**
     * Normalize telephone to canonical format for storage/validation.
     * - Trim spaces
     * - Convert +84xxxxxxxxx -> 0xxxxxxxxx
     */
    private function normalize($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string)$value);

        if ($value === '') {
            return '';
        }

        if (strpos($value, '+84') === 0) {
            $value = '0' . substr($value, 3);
        }

        return $value;
    }

    /**
     * Ensure normalized value is what gets persisted to DB.
     */
    public function beforeSave($object)
    {
        $code  = $this->getAttribute()->getAttributeCode();
        $value = $this->normalize($object->getData($code));

        $object->setData($code, $value);

        return parent::beforeSave($object);
    }

    /**
     * Validate the value (using normalized form).
     */
    public function validate($object)
    {
        $code  = $this->getAttribute()->getAttributeCode();
        $value = $this->normalize($object->getData($code));

        // Keep object data consistent with validated value (optional but nice)
        $object->setData($code, $value);

        if ($value === null || $value === '') {
            return true;
        }

        if (!ctype_digit($value)) {
            throw new LocalizedException(__('Số điện thoại chỉ được chứa các ký tự số.'));
        }

        if (strlen($value) > 10) {
            throw new LocalizedException(__('Số điện thoại không được quá 10 chữ số.'));
        }

        if (substr($value, 0, 1) !== '0') {
            throw new LocalizedException(__('Số điện thoại phải bắt đầu bằng số 0.'));
        }

        return true;
    }
}
