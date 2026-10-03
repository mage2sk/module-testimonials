<?php
declare(strict_types=1);

namespace Panth\Testimonials\Test\Unit\Block;

use Magento\Framework\DB\Select;
use Panth\Core\Helper\Theme;
use Panth\Testimonials\Block\Submit;
use Panth\Testimonials\Block\View;
use Panth\Testimonials\Block\Widget\TestimonialSlider;
use Panth\Testimonials\Helper\Data;
use Panth\Testimonials\Model\Category;
use Panth\Testimonials\Model\CategoryFactory;
use Panth\Testimonials\Model\ResourceModel\Category as CategoryResource;
use Panth\Testimonials\Model\ResourceModel\Testimonial\Collection;
use Panth\Testimonials\Model\ResourceModel\Testimonial\CollectionFactory;
use Panth\Testimonials\Model\Testimonial;

class SliderViewSubmitTest extends BlockTestCase
{
    private array $calls = [];
    private ?Testimonial $found = null;
    private array $categoryRow = [];

    private function collectionFactory(): CollectionFactory
    {
        $select = $this->createStub(Select::class);
        $select->method('orderRand')->willReturnCallback(function () use ($select) {
            $this->calls['orderRand'] = [];
            return $select;
        });
        $collection = $this->createStub(Collection::class);
        foreach (['addApprovedFilter', 'addStoreFilter', 'addCategoryFilter', 'addFeaturedFilter', 'setPageSize', 'addFieldToFilter'] as $method) {
            $collection->method($method)->willReturnCallback(function (...$args) use ($method, $collection) {
                $this->calls[$method][] = $args;
                return $collection;
            });
        }
        $collection->method('getSelect')->willReturn($select);
        $collection->method('getFirstItem')->willReturnCallback(fn() => $this->found ?? $this->testimonial());
        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);
        return $factory;
    }

    private function slider(array $data = [], bool $hyva = false): TestimonialSlider
    {
        $theme = $this->createStub(Theme::class);
        $theme->method('isHyva')->willReturn($hyva);
        return new TestimonialSlider($this->context(), $this->collectionFactory(), $this->storeManager, $theme, $data);
    }

    private function helper(): Data
    {
        $helper = $this->createStub(Data::class);
        $helper->method('getBaseUrl')->willReturn('testimonials');
        $helper->method('isSubmitEnabled')->willReturn(true);
        return $helper;
    }

    private function view(): View
    {
        $categoryFactory = $this->createStub(CategoryFactory::class);
        $categoryFactory->method('create')->willReturnCallback(fn() => $this->category());
        $resource = $this->createStub(CategoryResource::class);
        $resource->method('load')->willReturnCallback(function (Category $category, $id) use ($resource) {
            if ((int) ($this->categoryRow['category_id'] ?? 0) === (int) $id) {
                $category->setData($this->categoryRow);
            }
            return $resource;
        });
        return new View($this->context(), $this->collectionFactory(), $categoryFactory, $resource, $this->helper());
    }

    public function testSliderConfigDefaults(): void
    {
        $this->assertSame([
            'title' => '',
            'count' => 8,
            'show_rating' => true,
            'show_company' => true,
            'show_image' => true,
            'autoplay' => true,
            'autoplay_interval' => 5000,
            'featured_only' => false,
        ], $this->slider()->getSliderConfig());
    }

    public function testSliderConfigHonoursWidgetParameters(): void
    {
        $config = $this->slider([
            'title' => 'What clients say',
            'count' => '3',
            'show_rating' => '0',
            'autoplay' => '0',
            'autoplay_interval' => '8000',
            'featured_only' => '1',
        ])->getSliderConfig();

        $this->assertSame('What clients say', $config['title']);
        $this->assertSame(3, $config['count']);
        $this->assertFalse($config['show_rating']);
        $this->assertFalse($config['autoplay']);
        $this->assertSame(8000, $config['autoplay_interval']);
        $this->assertTrue($config['featured_only']);
    }

    public function testSliderCollectionAppliesWidgetFilters(): void
    {
        $slider = $this->slider(['category_id' => '4', 'featured_only' => '1', 'count' => '5']);

        $this->assertSame($slider->getTestimonials(), $slider->getTestimonials());
        $this->assertSame([[2]], $this->calls['addStoreFilter']);
        $this->assertSame([[4]], $this->calls['addCategoryFilter']);
        $this->assertCount(1, $this->calls['addFeaturedFilter']);
        $this->assertSame([[5]], $this->calls['setPageSize']);
        $this->assertArrayHasKey('orderRand', $this->calls);
    }

    public function testSliderCollectionDefaultsWithoutFilters(): void
    {
        $this->slider()->getTestimonials();

        $this->assertArrayNotHasKey('addCategoryFilter', $this->calls);
        $this->assertArrayNotHasKey('addFeaturedFilter', $this->calls);
        $this->assertSame([[8]], $this->calls['setPageSize']);
    }

    public function testSliderIdDerivesFromLayoutNameAndTemplateSwitchesForHyva(): void
    {
        $slider = $this->slider();
        $slider->setNameInLayout('home.testimonial.slider');

        $this->assertSame('pt-slider-home-testimonial-slider', $slider->getSliderId());
        $this->assertSame('Panth_Testimonials::widget/slider.phtml', $slider->getTemplate());
        $this->assertSame('Panth_Testimonials::hyva/widget/slider.phtml', $this->slider([], true)->getTemplate());
    }

    public function testSliderRendersNothingWhenModuleDisabled(): void
    {
        $method = new \ReflectionMethod(TestimonialSlider::class, '_toHtml');

        $this->assertSame('', $method->invoke($this->slider()));
    }

    public function testViewFindsApprovedTestimonialByUrlKey(): void
    {
        $this->params = ['url_key' => 'great'];
        $this->found = $this->testimonial(['testimonial_id' => 3, 'title' => 'Great']);
        $view = $this->view();

        $this->assertSame($this->found, $view->getTestimonial());
        $this->assertContains(['url_key', 'great'], $this->calls['addFieldToFilter']);
        $this->assertContains(['status', Testimonial::STATUS_APPROVED], $this->calls['addFieldToFilter']);
        $this->assertSame('https://shop.test/testimonials', $view->getBackUrl());
    }

    public function testViewReturnsNullWithoutMatch(): void
    {
        $this->params = ['url_key' => 'missing'];
        $view = $this->view();

        $this->assertNull($view->getTestimonial());
        $this->assertNull($view->getCategoryName());
        $this->assertNull($view->getCategoryUrl());
    }

    public function testViewExposesActiveCategory(): void
    {
        $this->params = ['url_key' => 'great'];
        $this->found = $this->testimonial(['testimonial_id' => 3, 'category_id' => 6]);
        $this->categoryRow = ['category_id' => 6, 'name' => 'Support', 'url_key' => 'support', 'is_active' => 1];
        $view = $this->view();

        $this->assertSame('Support', $view->getCategoryName());
        $this->assertSame('https://shop.test/testimonials/category/support', $view->getCategoryUrl());
    }

    public function testViewHidesInactiveCategory(): void
    {
        $this->params = ['url_key' => 'great'];
        $this->found = $this->testimonial(['testimonial_id' => 3, 'category_id' => 6]);
        $this->categoryRow = ['category_id' => 6, 'name' => 'Support', 'is_active' => 0];

        $this->assertNull($this->view()->getCategoryName());
    }

    public function testSubmitBlockDelegatesToHelperAndBuildsPostUrl(): void
    {
        $block = new Submit($this->context(), $this->helper());

        $this->assertTrue($block->isEnabled());
        $this->assertSame('https://shop.test/testimonials/submit/save', $block->getSubmitPostUrl());
    }
}
