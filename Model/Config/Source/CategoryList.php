<?php
/**
 * Copyright (c) Panth Infotech. All rights reserved.
 */
declare(strict_types=1);

namespace Panth\Testimonials\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Panth\Testimonials\Model\ResourceModel\Category\CollectionFactory;

class CategoryList implements OptionSourceInterface
{
    private ?array $options = null;

    public function __construct(
        private readonly CollectionFactory $collectionFactory
    ) {}

    /**
     * @return array<int, array{value: int|string, label: string}>
     */
    public function toOptionArray(): array
    {
        if ($this->options === null) {
            $this->options = [
                ['value' => '', 'label' => __('-- All Categories --')],
            ];

            $collection = $this->collectionFactory->create();
            $collection->addActiveFilter()->addDefaultOrder();

            foreach ($collection as $category) {
                $this->options[] = [
                    'value' => $category->getId(),
                    'label' => $category->getName(),
                ];
            }
        }

        return $this->options;
    }
}
