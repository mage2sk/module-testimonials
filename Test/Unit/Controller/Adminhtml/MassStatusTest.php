<?php
declare(strict_types=1);

namespace Panth\Testimonials\Test\Unit\Controller\Adminhtml;

use Magento\Ui\Component\MassAction\Filter;
use Panth\Testimonials\Controller\Adminhtml\Testimonial\MassStatus;
use Panth\Testimonials\Model\ResourceModel\Testimonial as TestimonialResource;
use Panth\Testimonials\Model\ResourceModel\Testimonial\Collection;
use Panth\Testimonials\Model\ResourceModel\Testimonial\CollectionFactory;
use Panth\Testimonials\Model\Testimonial;

class MassStatusTest extends AdminControllerTestCase
{
    private function items(array $statuses): array
    {
        $items = [];
        foreach ($statuses as $i => $status) {
            $item = (new \ReflectionClass(Testimonial::class))->newInstanceWithoutConstructor();
            $item->setData(['testimonial_id' => $i + 1, 'status' => $status]);
            $items[] = $item;
        }
        return $items;
    }

    private function controller(array $items, TestimonialResource $resource, ?Filter $filter = null): MassStatus
    {
        $collection = $this->createStub(Collection::class);
        $collection->method('getIterator')->willReturn(new \ArrayIterator($items));
        $collectionFactory = $this->createStub(CollectionFactory::class);
        $collectionFactory->method('create')->willReturn($collection);
        if ($filter === null) {
            $filter = $this->createStub(Filter::class);
            $filter->method('getCollection')->willReturn($collection);
        }

        return new MassStatus($this->backendContext(), $filter, $collectionFactory, $resource);
    }

    public function testApproveSavesOnlyItemsWhoseStatusChanges(): void
    {
        $items = $this->items([Testimonial::STATUS_PENDING, Testimonial::STATUS_APPROVED, Testimonial::STATUS_REJECTED]);
        $saved = [];
        $resource = $this->createMock(TestimonialResource::class);
        $resource->expects($this->exactly(2))->method('save')->willReturnCallback(function ($item) use (&$saved, $resource) {
            $saved[] = [(int) $item->getData('testimonial_id'), (int) $item->getData('status')];
            return $resource;
        });
        $this->params = ['status' => '1'];

        $this->controller($items, $resource)->execute();

        $this->assertSame([[1, 1], [3, 1]], $saved);
        $this->assertSame(['Updated the status of 2 testimonial(s).'], $this->successMessages);
        $this->assertSame(['*/*/', []], $this->redirect);
    }

    public function testRejectAndPendingStatusesAreAccepted(): void
    {
        foreach ([Testimonial::STATUS_REJECTED, Testimonial::STATUS_PENDING] as $status) {
            $this->successMessages = [];
            $resource = $this->createMock(TestimonialResource::class);
            $resource->expects($this->once())->method('save');
            $this->params = ['status' => (string) $status];
            $other = $status === Testimonial::STATUS_PENDING ? Testimonial::STATUS_APPROVED : Testimonial::STATUS_PENDING;

            $this->controller($this->items([$other]), $resource)->execute();

            $this->assertSame(['Updated the status of 1 testimonial(s).'], $this->successMessages);
        }
    }

    public function testInvalidOrMissingStatusIsRejectedWithoutTouchingRows(): void
    {
        foreach ([null, '7', 'abc', '-1'] as $value) {
            $this->errorMessages = [];
            $this->params = $value === null ? [] : ['status' => $value];
            $filter = $this->createMock(Filter::class);
            $filter->expects($this->never())->method('getCollection');
            $resource = $this->createMock(TestimonialResource::class);
            $resource->expects($this->never())->method('save');

            $this->controller($this->items([0]), $resource, $filter)->execute();

            $this->assertSame(['Please choose a valid status.'], $this->errorMessages);
            $this->assertSame(['*/*/', []], $this->redirect);
        }
    }

    public function testSaveFailureIsReportedAsError(): void
    {
        $resource = $this->createStub(TestimonialResource::class);
        $resource->method('save')->willThrowException(new \RuntimeException('db down'));
        $this->params = ['status' => '1'];

        $this->controller($this->items([0]), $resource)->execute();

        $this->assertSame(['db down'], $this->errorMessages);
        $this->assertSame([], $this->successMessages);
    }

    public function testAdminResourceMatchesManagePermission(): void
    {
        $this->assertSame('Panth_Testimonials::manage_testimonials', MassStatus::ADMIN_RESOURCE);
    }
}
