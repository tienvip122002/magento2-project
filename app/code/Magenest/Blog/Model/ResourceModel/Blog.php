<?php
namespace Magenest\Blog\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Exception\LocalizedException;

class Blog extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('magenest_blog', 'id');
    }

    // Logic kiểm tra trước khi lưu vào DB
    protected function _beforeSave(AbstractModel $object)
    {
        // 1. Lấy url_rewrite người dùng nhập vào
        $urlKey = $object->getData('url_rewrite');

        // 2. Tạo câu truy vấn kiểm tra trùng lặp
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable(), 'id')
            ->where('url_rewrite = ?', $urlKey);

        // Nếu đang là Sửa (đã có ID), thì phải loại trừ chính nó ra
        if ($object->getId()) {
            $select->where('id != ?', $object->getId());
        }

        // 3. Thực thi query
        $duplicateId = $connection->fetchOne($select);

        // 4. Nếu tìm thấy ID trùng -> Báo lỗi chặn lại ngay
        if ($duplicateId) {
            throw new LocalizedException(
                __('URL Rewrite "%1" already exists. Please choose another one.', $urlKey)
            );
        }

        return parent::_beforeSave($object);
    }
}