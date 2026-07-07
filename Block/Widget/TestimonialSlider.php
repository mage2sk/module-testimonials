<?php
declare(strict_types=1);

namespace Panth\Testimonials\Block\Widget;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Widget\Block\BlockInterface;
use Magento\Store\Model\StoreManagerInterface;
use Panth\Core\Helper\Theme;
use Panth\Testimonials\Model\ResourceModel\Testimonial\Collection;
use Panth\Testimonials\Model\ResourceModel\Testimonial\CollectionFactory;
use Panth\Testimonials\Model\Testimonial;

class TestimonialSlider extends Template implements BlockInterface
{
    protected $_template = 'Panth_Testimonials::widget/slider.phtml';

    private ?Collection $testimonialCollection = null;

    public function __construct(
        Context $context,
        private readonly CollectionFactory $collectionFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly Theme $themeHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getTemplate(): string
    {
        if ($this->themeHelper->isHyva()) {
            return 'Panth_Testimonials::hyva/widget/slider.phtml';
        }

        return parent::getTemplate();
    }

    public function getTestimonials(): Collection
    {
        if ($this->testimonialCollection === null) {
            $this->testimonialCollection = $this->collectionFactory->create();
            $this->testimonialCollection->addApprovedFilter()
                                        ->addStoreFilter((int) $this->storeManager->getStore()->getId());

            $this->testimonialCollection->getSelect()->orderRand();

            $categoryId = $this->getData('category_id');
            if ($categoryId) {
                $this->testimonialCollection->addCategoryFilter((int) $categoryId);
            }

            if ($this->getData('featured_only')) {
                $this->testimonialCollection->addFeaturedFilter();
            }

            $count = (int) ($this->getData('count') ?: 8);
            $this->testimonialCollection->setPageSize($count);
        }

        return $this->testimonialCollection;
    }

    public function getSliderConfig(): array
    {
        return [
            'title' => (string) ($this->getData('title') ?: ''),
            'count' => (int) ($this->getData('count') ?: 8),
            'show_rating' => (bool) ($this->getData('show_rating') ?? true),
            'show_company' => (bool) ($this->getData('show_company') ?? true),
            'show_image' => (bool) ($this->getData('show_image') ?? true),
            'autoplay' => (bool) ($this->getData('autoplay') ?? true),
            'autoplay_interval' => (int) ($this->getData('autoplay_interval') ?: 5000),
            'featured_only' => (bool) ($this->getData('featured_only') ?? false),
        ];
    }

    private ?string $sliderId = null;

    public function getSliderId(): string
    {
        if ($this->sliderId === null) {
            $this->sliderId = 'pt-slider-' . str_replace('.', '-', $this->getNameInLayout());
        }
        return $this->sliderId;
    }
}
