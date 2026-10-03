<?php
declare(strict_types=1);

namespace Panth\Testimonials\Test\Unit\Block;

use Panth\Testimonials\Block\Testimonials;
use Panth\Testimonials\Helper\Data;
use Panth\Testimonials\Model\Category;
use Panth\Testimonials\Model\ResourceModel\Category\Collection as CategoryCollection;
use Panth\Testimonials\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Panth\Testimonials\Model\ResourceModel\Testimonial\Collection as TestimonialCollection;
use Panth\Testimonials\Model\ResourceModel\Testimonial\CollectionFactory as TestimonialCollectionFactory;
use Panth\Testimonials\Model\Testimonial;

class TestimonialsTest extends BlockTestCase
{
    private ?Category $foundCategory = null;
    private array $categoryFilters = [];
    private array $testimonialCalls = [];
    private int $testimonialCreates = 0;
    private string $pageTitle = 'Customer Testimonials';

    private function block(): Testimonials
    {
        $testimonialCollection = $this->createStub(TestimonialCollection::class);
        foreach (['addApprovedFilter', 'addStoreFilter', 'addDefaultOrder', 'addCategoryFilter', 'setPageSize', 'setCurPage'] as $method) {
            $testimonialCollection->method($method)->willReturnCallback(
                function (...$args) use ($method, $testimonialCollection) {
                    $this->testimonialCalls[$method] = $args;
                    return $testimonialCollection;
                }
            );
        }
        $testimonialFactory = $this->createStub(TestimonialCollectionFactory::class);
        $testimonialFactory->method('create')->willReturnCallback(function () use ($testimonialCollection) {
            $this->testimonialCreates++;
            return $testimonialCollection;
        });

        $categoryCollection = $this->createStub(CategoryCollection::class);
        $categoryCollection->method('addFieldToFilter')->willReturnCallback(
            function ($field, $value) use ($categoryCollection) {
                $this->categoryFilters[$field] = $value;
                return $categoryCollection;
            }
        );
        $categoryCollection->method('addStoreFilter')->willReturnSelf();
        $categoryCollection->method('addActiveFilter')->willReturnSelf();
        $categoryCollection->method('addDefaultOrder')->willReturnSelf();
        $categoryCollection->method('setPageSize')->willReturnSelf();
        $categoryCollection->method('getFirstItem')->willReturnCallback(
            fn() => $this->foundCategory ?? $this->category()
        );
        $categoryFactory = $this->createStub(CategoryCollectionFactory::class);
        $categoryFactory->method('create')->willReturn($categoryCollection);

        $helper = $this->createStub(Data::class);
        $helper->method('getItemsPerPage')->willReturn(9);
        $helper->method('getBaseUrl')->willReturn('testimonials');
        $helper->method('getPageTitle')->willReturnCallback(fn() => $this->pageTitle);
        $helper->method('isSubmitEnabled')->willReturn(true);

        return new Testimonials($this->context(), $testimonialFactory, $categoryFactory, $helper, $this->storeManager);
    }

    public function testTestimonialCollectionIsFilteredPagedAndMemoized(): void
    {
        $this->params = ['category' => '5', 'p' => '3'];
        $block = $this->block();

        $first = $block->getTestimonials();
        $this->assertSame($first, $block->getTestimonials());
        $this->assertSame(1, $this->testimonialCreates);
        $this->assertSame([2], $this->testimonialCalls['addStoreFilter']);
        $this->assertSame([5], $this->testimonialCalls['addCategoryFilter']);
        $this->assertSame([9], $this->testimonialCalls['setPageSize']);
        $this->assertSame([3], $this->testimonialCalls['setCurPage']);
    }

    public function testInvalidPageIsClampedToFirstPageAndNoCategoryFilterWithoutSelection(): void
    {
        $this->params = ['p' => '-4'];
        $this->block()->getTestimonials();

        $this->assertSame([1], $this->testimonialCalls['setCurPage']);
        $this->assertArrayNotHasKey('addCategoryFilter', $this->testimonialCalls);
    }

    public function testSelectedCategoryResolvesFromUrlKey(): void
    {
        $this->params = ['url_key' => 'shipping'];
        $this->foundCategory = $this->category(['category_id' => 8, 'name' => 'Shipping']);

        $this->assertSame('8', $this->block()->getSelectedCategory());
        $this->assertSame('shipping', $this->categoryFilters['url_key']);
        $this->assertSame(1, $this->categoryFilters['is_active']);
    }

    public function testSelectedCategoryIsNullForUnknownUrlKeyOrArrayParam(): void
    {
        $this->params = ['url_key' => 'missing'];
        $this->assertNull($this->block()->getSelectedCategory());

        $this->params = ['url_key' => ['x']];
        $this->assertNull($this->block()->getSelectedCategory());
    }

    public function testCurrentCategoryFallsBackToCategoryIdParam(): void
    {
        $this->params = ['category' => '8'];
        $this->foundCategory = $this->category(['category_id' => 8, 'name' => 'Shipping']);

        $this->assertSame($this->foundCategory, $this->block()->getCurrentCategory());
        $this->assertSame(8, $this->categoryFilters['category_id']);
    }

    public function testCurrentCategoryIsNullWithoutParams(): void
    {
        $this->assertNull($this->block()->getCurrentCategory());
    }

    public function testH1Variants(): void
    {
        $this->assertSame('Customer Testimonials', $this->block()->getH1());

        $this->params = ['p' => '2'];
        $this->assertSame('Customer Testimonials - Page 2', $this->block()->getH1());

        $this->params = ['url_key' => 'shipping', 'p' => '2'];
        $this->foundCategory = $this->category(['category_id' => 8, 'name' => 'Shipping']);
        $this->assertSame('Shipping - Customer Testimonials', $this->block()->getH1());
    }

    public function testH1UsesFallbackWhenTitleIsEmpty(): void
    {
        $this->pageTitle = '';

        $this->assertSame('Client Testimonials', $this->block()->getH1());
    }

    public function testUrlBuilders(): void
    {
        $block = $this->block();

        $this->assertSame(
            'https://shop.test/testimonials/great',
            $block->getTestimonialUrl($this->testimonial(['url_key' => 'great']))
        );
        $this->assertSame(
            'https://shop.test/testimonials/category/shipping',
            $block->getCategoryUrl($this->category(['url_key' => 'shipping']))
        );
        $this->assertSame('https://shop.test/testimonials/page/4', $block->getPageUrl(4));
        $this->assertSame('https://shop.test/testimonials/submit', $block->getSubmitUrl());
        $this->assertTrue($block->isSubmitEnabled());
        $this->assertSame(9, $block->getItemsPerPage());
        $this->assertSame([Testimonial::CACHE_TAG, Category::CACHE_TAG], $block->getIdentities());
    }

    public function testCustomerImageUrl(): void
    {
        $block = $this->block();

        $this->assertNull($block->getCustomerImageUrl(null));
        $this->assertNull($block->getCustomerImageUrl($this->testimonial()));
        $this->assertSame(
            'https://shop.test/media/testimonials/jane.jpg',
            $block->getCustomerImageUrl($this->testimonial(['customer_image' => 'testimonials/jane.jpg']))
        );
        $this->assertNull($block->getCustomerImageUrl($this->testimonial(['customer_image' => 'javascript:x'])));
    }
}
