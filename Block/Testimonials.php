<?php
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

            $currentPage = (int) $this->getRequest()->getParam('p', 1);
            if ($currentPage < 1) $currentPage = 1;
            $this->testimonialCollection->setCurPage($currentPage);
        }

        return $this->testimonialCollection;
    }

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

    public function getSelectedCategory(): ?string
    {
        $categoryId = $this->getRequest()->getParam('category');
        if ($categoryId) {
            return $categoryId;
        }

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

    public function getCurrentCategory(): ?Category
    {
        $urlKey = (string) $this->getRequest()->getParam('url_key', '');
        if ($urlKey !== '') {
            $catCollection = $this->categoryCollectionFactory->create();
            $catCollection->addFieldToFilter('url_key', $urlKey)
                          ->addFieldToFilter('is_active', 1)
                          ->setPageSize(1);
            $cat = $catCollection->getFirstItem();
            if ($cat->getId()) {
                return $cat;
            }
        }

        $categoryId = (int) $this->getRequest()->getParam('category', 0);
        if ($categoryId > 0) {
            $catCollection = $this->categoryCollectionFactory->create();
            $catCollection->addFieldToFilter('category_id', $categoryId)
                          ->addFieldToFilter('is_active', 1)
                          ->setPageSize(1);
            $cat = $catCollection->getFirstItem();
            if ($cat->getId()) {
                return $cat;
            }
        }

        return null;
    }

    public function getH1(): string
    {
        $base = $this->helper->getPageTitle();
        if ($base === '') {
            $base = (string) __('Client Testimonials');
        }

        $category = $this->getCurrentCategory();
        if ($category && $category->getName()) {
            return sprintf('%s — %s', $category->getName(), $base);
        }

        $page = (int) $this->getRequest()->getParam('p', 1);
        if ($page < 1) {
            $page = 1;
        }
        if ($page > 1) {
            return sprintf('%s — %s %d', $base, (string) __('Page'), $page);
        }

        return $base;
    }

    public function getTestimonialUrl(Testimonial $testimonial): string
    {
        return $this->getBaseUrl() . $this->helper->getBaseUrl() . '/' . $testimonial->getUrlKey();
    }

    public function getCategoryUrl(Category $category): string
    {
        return $this->getBaseUrl() . $this->helper->getBaseUrl() . '/category/' . $category->getUrlKey();
    }

    public function getPageUrl(int $page): string
    {
        return $this->getBaseUrl() . $this->helper->getBaseUrl() . '/page/' . $page;
    }

    public function getSubmitUrl(): string
    {
        return $this->getBaseUrl() . $this->helper->getBaseUrl() . '/submit';
    }

    public function isSubmitEnabled(): bool
    {
        return $this->helper->isSubmitEnabled();
    }

    public function getItemsPerPage(): int
    {
        return $this->helper->getItemsPerPage();
    }
}
