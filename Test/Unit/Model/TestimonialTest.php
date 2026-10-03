<?php
declare(strict_types=1);

namespace Panth\Testimonials\Test\Unit\Model;

use Panth\Testimonials\Model\Category;
use Panth\Testimonials\Model\Testimonial;
use PHPUnit\Framework\TestCase;

class TestimonialTest extends TestCase
{
    private function testimonial(array $data = [], array $orig = []): Testimonial
    {
        $model = (new \ReflectionClass(Testimonial::class))->newInstanceWithoutConstructor();
        $model->setData($data);
        foreach ($orig as $key => $value) {
            $model->setOrigData($key, $value);
        }
        return $model;
    }

    public function testIdentitiesForUnapprovedTestimonialOnlyContainOwnTag(): void
    {
        $model = $this->testimonial(['status' => Testimonial::STATUS_PENDING]);
        $model->setId(5);

        $this->assertSame(['panth_testimonial_5'], $model->getIdentities());
    }

    public function testIdentitiesForApprovedTestimonialIncludeListTag(): void
    {
        $model = $this->testimonial(['status' => Testimonial::STATUS_APPROVED]);
        $model->setId(9);

        $this->assertSame(['panth_testimonial_9', Testimonial::CACHE_TAG], $model->getIdentities());
    }

    public function testIdentitiesIncludeListTagWhenTestimonialWasApprovedBefore(): void
    {
        $model = $this->testimonial(
            ['status' => Testimonial::STATUS_REJECTED],
            ['status' => Testimonial::STATUS_APPROVED]
        );
        $model->setId(2);

        $this->assertSame(['panth_testimonial_2', Testimonial::CACHE_TAG], $model->getIdentities());
    }

    public function testTypedAccessorsCastStoredValues(): void
    {
        $model = $this->testimonial([
            'testimonial_id' => '4',
            'category_id' => '8',
            'rating' => '3',
            'status' => '1',
            'is_featured' => '1',
            'sort_order' => '10',
            'store_id' => '2',
        ]);

        $this->assertSame(4, $model->getTestimonialId());
        $this->assertSame(8, $model->getCategoryId());
        $this->assertSame(3, $model->getRating());
        $this->assertSame(1, $model->getStatus());
        $this->assertTrue($model->getIsFeatured());
        $this->assertSame(10, $model->getSortOrder());
        $this->assertSame(2, $model->getStoreId());
    }

    public function testMissingIdsAreNullAndNumbersDefaultToZero(): void
    {
        $model = $this->testimonial();

        $this->assertNull($model->getTestimonialId());
        $this->assertNull($model->getCategoryId());
        $this->assertSame(0, $model->getRating());
        $this->assertSame(0, $model->getStatus());
        $this->assertFalse($model->getIsFeatured());
    }

    public function testFeaturedFlagIsStoredAsInteger(): void
    {
        $model = $this->testimonial();
        $model->setIsFeatured(true);

        $this->assertSame(1, $model->getData('is_featured'));
    }

    public function testCategoryIdentitiesAlwaysIncludeListTag(): void
    {
        $category = (new \ReflectionClass(Category::class))->newInstanceWithoutConstructor();
        $category->setId(3);
        $category->setIsActive(true);

        $this->assertSame(['panth_testimonial_category_3', Category::CACHE_TAG], $category->getIdentities());
        $this->assertSame(1, $category->getData('is_active'));
        $this->assertTrue($category->getIsActive());
        $this->assertNull($category->getCategoryId());
    }
}
