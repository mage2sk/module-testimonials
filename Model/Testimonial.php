<?php
/**
 * Copyright (c) Panth Infotech. All rights reserved.
 */
declare(strict_types=1);

namespace Panth\Testimonials\Model;

use Magento\Framework\Model\AbstractModel;
use Panth\Testimonials\Model\ResourceModel\Testimonial as TestimonialResource;

class Testimonial extends AbstractModel
{
    public const CACHE_TAG = 'panth_testimonial';

    public const STATUS_PENDING = 0;
    public const STATUS_APPROVED = 1;
    public const STATUS_REJECTED = 2;

    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * @var string
     */
    protected $_eventPrefix = 'panth_testimonial';

    protected function _construct(): void
    {
        $this->_init(TestimonialResource::class);
    }

    public function getIdentities(): array
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getTestimonialId(): ?int
    {
        $id = $this->getData('testimonial_id');
        return $id !== null ? (int) $id : null;
    }

    public function setTestimonialId(int $testimonialId): self
    {
        return $this->setData('testimonial_id', $testimonialId);
    }

    public function getCategoryId(): ?int
    {
        $id = $this->getData('category_id');
        return $id !== null ? (int) $id : null;
    }

    public function setCategoryId(?int $categoryId): self
    {
        return $this->setData('category_id', $categoryId);
    }

    public function getCustomerName(): ?string
    {
        return $this->getData('customer_name');
    }

    public function setCustomerName(string $customerName): self
    {
        return $this->setData('customer_name', $customerName);
    }

    public function getCustomerEmail(): ?string
    {
        return $this->getData('customer_email');
    }

    public function setCustomerEmail(?string $customerEmail): self
    {
        return $this->setData('customer_email', $customerEmail);
    }

    public function getCustomerTitle(): ?string
    {
        return $this->getData('customer_title');
    }

    public function setCustomerTitle(?string $customerTitle): self
    {
        return $this->setData('customer_title', $customerTitle);
    }

    public function getCustomerCompany(): ?string
    {
        return $this->getData('customer_company');
    }

    public function setCustomerCompany(?string $customerCompany): self
    {
        return $this->setData('customer_company', $customerCompany);
    }

    public function getCustomerImage(): ?string
    {
        return $this->getData('customer_image');
    }

    public function setCustomerImage(?string $customerImage): self
    {
        return $this->setData('customer_image', $customerImage);
    }

    public function getRating(): int
    {
        return (int) $this->getData('rating');
    }

    public function setRating(int $rating): self
    {
        return $this->setData('rating', $rating);
    }

    public function getTitle(): ?string
    {
        return $this->getData('title');
    }

    public function setTitle(string $title): self
    {
        return $this->setData('title', $title);
    }

    public function getContent(): ?string
    {
        return $this->getData('content');
    }

    public function setContent(string $content): self
    {
        return $this->setData('content', $content);
    }

    public function getShortContent(): ?string
    {
        return $this->getData('short_content');
    }

    public function setShortContent(?string $shortContent): self
    {
        return $this->setData('short_content', $shortContent);
    }

    public function getUrlKey(): ?string
    {
        return $this->getData('url_key');
    }

    public function setUrlKey(string $urlKey): self
    {
        return $this->setData('url_key', $urlKey);
    }

    public function getStatus(): int
    {
        return (int) $this->getData('status');
    }

    public function setStatus(int $status): self
    {
        return $this->setData('status', $status);
    }

    public function getIsFeatured(): bool
    {
        return (bool) $this->getData('is_featured');
    }

    public function setIsFeatured(bool $isFeatured): self
    {
        return $this->setData('is_featured', (int) $isFeatured);
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

    public function getMetaTitle(): ?string
    {
        return $this->getData('meta_title');
    }

    public function setMetaTitle(?string $metaTitle): self
    {
        return $this->setData('meta_title', $metaTitle);
    }

    public function getMetaDescription(): ?string
    {
        return $this->getData('meta_description');
    }

    public function setMetaDescription(?string $metaDescription): self
    {
        return $this->setData('meta_description', $metaDescription);
    }

    public function getCreatedAt(): ?string
    {
        return $this->getData('created_at');
    }

    public function setCreatedAt(string $createdAt): self
    {
        return $this->setData('created_at', $createdAt);
    }

    public function getUpdatedAt(): ?string
    {
        return $this->getData('updated_at');
    }

    public function setUpdatedAt(string $updatedAt): self
    {
        return $this->setData('updated_at', $updatedAt);
    }
}
