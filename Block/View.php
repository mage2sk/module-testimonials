<?php
/**
 * Copyright (c) Panth Infotech. All rights reserved.
 */
declare(strict_types=1);

namespace Panth\Testimonials\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Panth\Testimonials\Helper\Data as DataHelper;
use Panth\Testimonials\Model\CategoryFactory;
use Panth\Testimonials\Model\ResourceModel\Category as CategoryResource;
use Panth\Testimonials\Model\ResourceModel\Testimonial\CollectionFactory;
use Panth\Testimonials\Model\Testimonial;

class View extends Template
{
    private ?Testimonial $currentTestimonial = null;

    public function __construct(
        Context $context,
        private readonly CollectionFactory $collectionFactory,
        private readonly CategoryFactory $categoryFactory,
        private readonly CategoryResource $categoryResource,
        private readonly DataHelper $helper,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Load and return the current testimonial by url_key
     */
    public function getTestimonial(): ?Testimonial
    {
        if ($this->currentTestimonial === null) {
            $urlKey = $this->getRequest()->getParam('url_key');
            if ($urlKey) {
                $collection = $this->collectionFactory->create();
                $collection->addFieldToFilter('url_key', $urlKey)
                           ->addFieldToFilter('status', Testimonial::STATUS_APPROVED)
                           ->setPageSize(1);

                $testimonial = $collection->getFirstItem();
                if ($testimonial->getId()) {
                    $this->currentTestimonial = $testimonial;
                }
            }
        }

        return $this->currentTestimonial;
    }

    /**
     * Get back URL to testimonials listing
     */
    public function getBackUrl(): string
    {
        return $this->getBaseUrl() . $this->helper->getBaseUrl();
    }

    /**
     * Resolve the category name for the current testimonial
     */
    public function getCategoryName(): ?string
    {
        $category = $this->loadCategory();
        return $category ? $category->getName() : null;
    }

    /**
     * Get URL for the testimonial's category using the configured route
     */
    public function getCategoryUrl(): ?string
    {
        $category = $this->loadCategory();
        if (!$category) {
            return null;
        }
        return $this->getBaseUrl() . $this->helper->getBaseUrl() . '/category/' . $category->getUrlKey();
    }

    private function loadCategory(): ?\Panth\Testimonials\Model\Category
    {
        $testimonial = $this->getTestimonial();
        if (!$testimonial || !$testimonial->getCategoryId()) {
            return null;
        }

        $category = $this->categoryFactory->create();
        $this->categoryResource->load($category, $testimonial->getCategoryId());

        return $category->getId() ? $category : null;
    }
}
