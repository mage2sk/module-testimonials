<?php
/**
 * Copyright (c) Panth Infotech. All rights reserved.
 */
declare(strict_types=1);

namespace Panth\Testimonials\Model\ResourceModel\Testimonial;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Panth\Testimonials\Model\Testimonial;
use Panth\Testimonials\Model\ResourceModel\Testimonial as TestimonialResource;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'testimonial_id';

    /**
     * @var string
     */
    protected $_eventPrefix = 'panth_testimonial_collection';

    /**
     * @var string
     */
    protected $_eventObject = 'testimonial_collection';

    protected function _construct(): void
    {
        $this->_init(Testimonial::class, TestimonialResource::class);
    }

    /**
     * Filter collection by approved status
     */
    public function addApprovedFilter(): self
    {
        return $this->addFieldToFilter('status', Testimonial::STATUS_APPROVED);
    }

    /**
     * Filter collection by store ID
     */
    public function addStoreFilter(int $storeId): self
    {
        return $this->addFieldToFilter('store_id', ['in' => [0, $storeId]]);
    }

    /**
     * Filter by category
     */
    public function addCategoryFilter(int $categoryId): self
    {
        return $this->addFieldToFilter('category_id', $categoryId);
    }

    /**
     * Filter by featured only
     */
    public function addFeaturedFilter(): self
    {
        return $this->addFieldToFilter('is_featured', 1);
    }

    /**
     * Set default sort order
     */
    public function addDefaultOrder(): self
    {
        return $this->setOrder('sort_order', self::SORT_ORDER_ASC)
                     ->setOrder('created_at', self::SORT_ORDER_DESC);
    }
}
