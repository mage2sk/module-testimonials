<?php
/**
 * Copyright (c) Panth Infotech. All rights reserved.
 */
declare(strict_types=1);

namespace Panth\Testimonials\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Panth\Testimonials\Helper\Data as DataHelper;
use Panth\Testimonials\Model\ResourceModel\Category\Collection as CategoryCollection;
use Panth\Testimonials\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Panth\Testimonials\Model\ResourceModel\Testimonial\Collection as TestimonialCollection;
use Panth\Testimonials\Model\ResourceModel\Testimonial\CollectionFactory as TestimonialCollectionFactory;
use Panth\Testimonials\Model\Testimonial;
use Panth\Testimonials\Model\Category;

class Testimonials extends Template
{
    private ?TestimonialCollection $testimonialCollection = null;
    private ?CategoryCollection $categoryCollection = null;

    public function __construct(
        Context $context,
        private readonly TestimonialCollectionFactory $testimonialCollectionFactory,
        private readonly CategoryCollectionFactory $categoryCollectionFactory,
        private readonly DataHelper $helper,
        private readonly StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get approved testimonials, filtered by store and optionally by category
     */
    public function getTestimonials(): TestimonialCollection
    {
        if ($this->testimonialCollection === null) {
            $this->testimonialCollection = $this->testimonialCollectionFactory->create();
            $this->testimonialCollection->addApprovedFilter()
                                        ->addStoreFilter((int) $this->storeManager->getStore()->getId())
                                        ->addDefaultOrder();

            $selectedCategory = $this->getSelectedCategory();
            if ($selectedCategory) {
                $this->testimonialCollection->addCategoryFilter((int) $selectedCategory);
            }

            $this->testimonialCollection->setPageSize($this->helper->getItemsPerPage());

            // Set current page from request
            $currentPage = (int) $this->getRequest()->getParam('p', 1);
            if ($currentPage < 1) $currentPage = 1;
            $this->testimonialCollection->setCurPage($currentPage);
        }

        return $this->testimonialCollection;
    }

    /**
     * Get active categories
     */
    public function getCategories(): CategoryCollection
    {
        if ($this->categoryCollection === null) {
            $this->categoryCollection = $this->categoryCollectionFactory->create();
            $this->categoryCollection->addActiveFilter()
                                     ->addStoreFilter((int) $this->storeManager->getStore()->getId())
                                     ->addDefaultOrder();
        }

        return $this->categoryCollection;
    }

    /**
     * Get selected category ID from request
     * Supports both 'category' (ID) and 'url_key' (slug) params
     */
    public function getSelectedCategory(): ?string
    {
        $categoryId = $this->getRequest()->getParam('category');
        if ($categoryId) {
            return $categoryId;
        }

        // Check url_key param (from Router on category pages)
        $urlKey = $this->getRequest()->getParam('url_key');
        if ($urlKey) {
            $catCollection = $this->categoryCollectionFactory->create();
            $catCollection->addFieldToFilter('url_key', $urlKey)
                          ->addFieldToFilter('is_active', 1)
                          ->setPageSize(1);
            $cat = $catCollection->getFirstItem();
            if ($cat->getId()) {
                return (string) $cat->getId();
            }
        }

        return null;
    }

    /**
     * Build testimonial detail URL
     */
    public function getTestimonialUrl(Testimonial $testimonial): string
    {
        return $this->getBaseUrl() . $this->helper->getBaseUrl() . '/' . $testimonial->getUrlKey();
    }

    /**
     * Build category URL
     */
    public function getCategoryUrl(Category $category): string
    {
        return $this->getBaseUrl() . $this->helper->getBaseUrl() . '/category/' . $category->getUrlKey();
    }

    /**
     * Build pagination URL for a given page number
     */
    public function getPageUrl(int $page): string
    {
        return $this->getBaseUrl() . $this->helper->getBaseUrl() . '/page/' . $page;
    }

    /**
     * Build submit form URL
     */
    public function getSubmitUrl(): string
    {
        return $this->getBaseUrl() . $this->helper->getBaseUrl() . '/submit';
    }

    /**
     * Check if submit form is enabled
     */
    public function isSubmitEnabled(): bool
    {
        return $this->helper->isSubmitEnabled();
    }

    /**
     * Get items per page from config
     */
    public function getItemsPerPage(): int
    {
        return $this->helper->getItemsPerPage();
    }
}
