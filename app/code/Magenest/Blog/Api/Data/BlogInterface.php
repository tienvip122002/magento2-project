<?php
namespace Magenest\Blog\Api\Data;

interface BlogInterface
{
    const ID = 'id';
    const TITLE = 'title';
    const CONTENT = 'content';
    const STATUS = 'status';
    const URL_REWRITE = 'url_rewrite';
    const AUTHOR_ID = 'author_id';

/**
     * Get ID
     * @return int|null
     */
    public function getId();

    /**
     * Set ID
     * @param int $id
     * @return $this
     */
    public function setId($id);

    /**
     * Get Title
     * @return string|null
     */
    public function getTitle();

    /**
     * Set Title
     * @param string $title
     * @return $this
     */
    public function setTitle($title);

    /**
     * Get Content
     * @return string|null
     */
    public function getContent();

    /**
     * Set Content
     * @param string $content
     * @return $this
     */
    public function setContent($content);

    /**
     * Get Status
     * @return int|null
     */
    public function getStatus();

    /**
     * Set Status
     * @param int $status
     * @return $this
     */
    public function setStatus($status);

    /**
     * Get URL Rewrite
     * @return string|null
     */
    public function getUrlRewrite();

    /**
     * Set URL Rewrite
     * @param string $urlRewrite
     * @return $this
     */
    public function setUrlRewrite($urlRewrite);

    /**
     * Get Author ID
     * @return int|null
     */
    public function getAuthorId();

    /**
     * Set Author ID
     * @param int $authorId
     * @return $this
     */
    public function setAuthorId($authorId);
}