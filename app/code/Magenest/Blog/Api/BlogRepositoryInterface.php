<?php
namespace Magenest\Blog\Api;

use Magenest\Blog\Api\Data\BlogInterface;

interface BlogRepositoryInterface
{
    /**
     * Save blog (Create or Update)
     * @param \Magenest\Blog\Api\Data\BlogInterface $blog
     * @return \Magenest\Blog\Api\Data\BlogInterface
     */
    public function save(BlogInterface $blog);

    /**
     * Get blog by ID
     * @param int $id
     * @return \Magenest\Blog\Api\Data\BlogInterface
     */
    public function getById($id);

    /**
     * Delete blog
     * @param \Magenest\Blog\Api\Data\BlogInterface $blog
     * @return bool
     */
    public function delete(BlogInterface $blog);

    /**
     * Delete blog by ID
     * @param int $id
     * @return bool
     */
    public function deleteById($id);
}