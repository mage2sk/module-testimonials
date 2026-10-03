<?php
declare(strict_types=1);

namespace Panth\Testimonials\Test\Unit\Controller\Adminhtml;

use Magento\Ui\Component\MassAction\Filter;
use Panth\Testimonials\Controller\Adminhtml\Category\Delete as CategoryDelete;
use Panth\Testimonials\Controller\Adminhtml\Testimonial\Delete as TestimonialDelete;
use Panth\Testimonials\Controller\Adminhtml\Testimonial\MassDelete;
use Panth\Testimonials\Model\Category;
use Panth\Testimonials\Model\CategoryFactory;
use Panth\Testimonials\Model\ResourceModel\Category as CategoryResource;
use Panth\Testimonials\Model\ResourceModel\Testimonial as TestimonialResource;
use Panth\Testimonials\Model\ResourceModel\Testimonial\Collection;
use Panth\Testimonials\Model\ResourceModel\Testimonial\CollectionFactory;
use Panth\Testimonials\Model\Testimonial;
use Panth\Testimonials\Model\TestimonialFactory;

class DeleteActionsTest extends AdminControllerTestCase
{
    private function testimonialFactory(): TestimonialFactory
    {
        $factory = $this->createStub(TestimonialFactory::class);
        $factory->method('create')->willReturnCallback(
            fn() => (new \ReflectionClass(Testimonial::class))->newInstanceWithoutConstructor()
        );
        return $factory;
    }

    public function testDeleteWithoutIdOnlyRedirects(): void
    {
        $resource = $this->createMock(TestimonialResource::class);
        $resource->expects($this->never())->method('delete');

        (new TestimonialDelete($this->backendContext(), $this->testimonialFactory(), $resource))->execute();

        $this->assertSame(['*/*/', []], $this->redirect);
        $this->assertSame([], $this->successMessages);
    }

    public function testDeleteLoadsAndDeletesTestimonial(): void
    {
        $this->params = ['id' => '12'];
        $resource = $this->createMock(TestimonialResource::class);
        $resource->expects($this->once())->method('load')
            ->with($this->isInstanceOf(Testimonial::class), 12)->willReturnSelf();
        $resource->expects($this->once())->method('delete')->willReturnSelf();

        (new TestimonialDelete($this->backendContext(), $this->testimonialFactory(), $resource))->execute();

        $this->assertSame(['Testimonial deleted.'], $this->successMessages);
        $this->assertSame(['*/*/', []], $this->redirect);
    }

    public function testDeleteFailureIsReported(): void
    {
        $this->params = ['id' => '12'];
        $resource = $this->createStub(TestimonialResource::class);
        $resource->method('delete')->willThrowException(new \RuntimeException('locked'));

        (new TestimonialDelete($this->backendContext(), $this->testimonialFactory(), $resource))->execute();

        $this->assertSame(['locked'], $this->errorMessages);
        $this->assertSame(['*/*/', []], $this->redirect);
    }

    public function testCategoryDeleteLoadsById(): void
    {
        $this->params = ['id' => '3'];
        $factory = $this->createStub(CategoryFactory::class);
        $factory->method('create')->willReturnCallback(
            fn() => (new \ReflectionClass(Category::class))->newInstanceWithoutConstructor()
        );
        $resource = $this->createMock(CategoryResource::class);
        $resource->expects($this->once())->method('load')->with($this->anything(), 3)->willReturnSelf();
        $resource->expects($this->once())->method('delete')->willReturnSelf();

        (new CategoryDelete($this->backendContext(), $factory, $resource))->execute();

        $this->assertCount(1, $this->successMessages);
        $this->assertSame(['*/*/', []], $this->redirect);
    }

    public function testMassDeleteRemovesEverySelectedItem(): void
    {
        $items = [];
        for ($i = 1; $i <= 3; $i++) {
            $item = (new \ReflectionClass(Testimonial::class))->newInstanceWithoutConstructor();
            $item->setId($i);
            $items[] = $item;
        }
        $collection = $this->createStub(Collection::class);
        $collection->method('getIterator')->willReturn(new \ArrayIterator($items));
        $collectionFactory = $this->createStub(CollectionFactory::class);
        $collectionFactory->method('create')->willReturn($collection);
        $filter = $this->createMock(Filter::class);
        $filter->expects($this->once())->method('getCollection')->with($collection)->willReturn($collection);

        $resource = $this->createMock(TestimonialResource::class);
        $resource->expects($this->exactly(3))->method('delete')->willReturnSelf();

        (new MassDelete($this->backendContext(), $filter, $collectionFactory, $resource))->execute();

        $this->assertSame(['Deleted 3 testimonial(s).'], $this->successMessages);
        $this->assertSame(['*/*/', []], $this->redirect);
    }
}
