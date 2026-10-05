<?php
declare(strict_types=1);

namespace Panth\Testimonials\Model\ResourceModel\Category;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Panth\Testimonials\Model\Category;
use Panth\Testimonials\Model\ResourceModel\Category as CategoryResource;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'category_id';

    protected $_eventPrefix = 'panth_testimonial_category_collection';

    protected $_eventObject = 'category_collection';

    protected function _construct(): void
    {
        $this->_init(Category::class, CategoryResource::class);
    }

    public function addActiveFilter(): self
    {
        return $this->addFieldToFilter('is_active', 1);
    }

    public function addStoreFilter(int $storeId): self
    {
        return $this->addFieldToFilter('store_id', ['in' => [0, $storeId]]);
    }

    public function addDefaultOrder(): self
    {
        return $this->setOrder('sort_order', self::SORT_ORDER_ASC);
    }
}
