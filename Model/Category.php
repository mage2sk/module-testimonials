<?php
/**
 * Copyright (c) Panth Infotech. All rights reserved.
 */
declare(strict_types=1);

namespace Panth\Testimonials\Model;

use Magento\Framework\Model\AbstractModel;
use Panth\Testimonials\Model\ResourceModel\Category as CategoryResource;

class Category extends AbstractModel
{
    public const CACHE_TAG = 'panth_testimonial_category';

    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * @var string
     */
    protected $_eventPrefix = 'panth_testimonial_category';

    protected function _construct(): void
    {
        $this->_init(CategoryResource::class);
    }

    public function getIdentities(): array
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getCategoryId(): ?int
    {
        $id = $this->getData('category_id');
        return $id !== null ? (int) $id : null;
    }

    public function setCategoryId(int $categoryId): self
    {
        return $this->setData('category_id', $categoryId);
    }

    public function getName(): ?string
    {
        return $this->getData('name');
    }

    public function setName(string $name): self
    {
        return $this->setData('name', $name);
    }

    public function getUrlKey(): ?string
    {
        return $this->getData('url_key');
    }

    public function setUrlKey(string $urlKey): self
    {
        return $this->setData('url_key', $urlKey);
    }

    public function getDescription(): ?string
    {
        return $this->getData('description');
    }

    public function setDescription(?string $description): self
    {
        return $this->setData('description', $description);
    }

    public function getIsActive(): bool
    {
        return (bool) $this->getData('is_active');
    }

    public function setIsActive(bool $isActive): self
    {
        return $this->setData('is_active', (int) $isActive);
    }

    public function getSortOrder(): int
    {
        return (int) $this->getData('sort_order');
    }

    public function setSortOrder(int $sortOrder): self
    {
        return $this->setData('sort_order', $sortOrder);
    }

    public function getStoreId(): int
    {
        return (int) $this->getData('store_id');
    }

    public function setStoreId(int $storeId): self
    {
        return $this->setData('store_id', $storeId);
    }
}
