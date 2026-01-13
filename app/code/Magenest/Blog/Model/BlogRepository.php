<?php
namespace Magenest\Blog\Model;

use Magenest\Blog\Api\BlogRepositoryInterface;
use Magenest\Blog\Api\Data\BlogInterface;
use Magenest\Blog\Model\BlogFactory;
use Magenest\Blog\Model\ResourceModel\Blog as BlogResource;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\CouldNotDeleteException;

class BlogRepository implements BlogRepositoryInterface
{
    protected $blogFactory;
    protected $blogResource;

    public function __construct(
        BlogFactory $blogFactory,
        BlogResource $blogResource
    ) {
        $this->blogFactory = $blogFactory;
        $this->blogResource = $blogResource;
    }

    public function save(BlogInterface $blog)
    {
        try {
            // Logic: Nếu $blog có ID -> Update, Không có ID -> Create
            // Lưu ý: Magento API tự map JSON vào object $blog rồi
            $this->blogResource->save($blog);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        return $blog;
    }

    public function getById($id)
    {
        $blog = $this->blogFactory->create();
        $this->blogResource->load($blog, $id);
        
        if (!$blog->getId()) {
            throw new NoSuchEntityException(__('Blog with id "%1" does not exist.', $id));
        }
        return $blog;
    }

    public function delete(BlogInterface $blog)
    {
        try {
            $this->blogResource->delete($blog);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    public function deleteById($id)
    {
        $blog = $this->getById($id); // Tận dụng hàm getById để check tồn tại
        return $this->delete($blog);
    }
}