<?php
namespace Magenest\CustomerMangement\Model\Attribute\Backend;

use Magento\Eav\Model\Entity\Attribute\Backend\AbstractBackend;
use Magento\Framework\Exception\LocalizedException;

class Telephone extends AbstractBackend
{
    public function validate($object)
    {
        $code = $this->getAttribute()->getAttributeCode();
        $value = $object->getData($code);

        if ($value === null || $value === '') return true;

        $value = trim($value);
        if (strpos($value, '+84') === 0) {
            $value = '0' . substr($value, 3);
        }
        $object->setData($code, $value);

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