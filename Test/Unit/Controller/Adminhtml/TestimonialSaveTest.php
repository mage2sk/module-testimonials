<?php
declare(strict_types=1);

namespace Panth\Testimonials\Test\Unit\Controller\Adminhtml;

use Panth\Testimonials\Controller\Adminhtml\Testimonial\Save;
use Panth\Testimonials\Model\ResourceModel\Testimonial as TestimonialResource;
use Panth\Testimonials\Model\Testimonial;
use Panth\Testimonials\Model\TestimonialFactory;

class TestimonialSaveTest extends AdminControllerTestCase
{
    private ?Testimonial $saved = null;
    private array $loadedIds = [];
    private ?\Throwable $saveException = null;

    private function controller(): Save
    {
        $factory = $this->createStub(TestimonialFactory::class);
        $factory->method('create')->willReturnCallback(
            function () {
                $model = (new \ReflectionClass(Testimonial::class))->newInstanceWithoutConstructor();
                $model->setIdFieldName('testimonial_id');
                return $model;
            }
        );

        $resource = $this->createStub(TestimonialResource::class);
        $resource->method('getConnection')->willReturn($this->connection());
        $resource->method('getMainTable')->willReturn('panth_testimonial');
        $resource->method('load')->willReturnCallback(function ($model, $id) use ($resource) {
            $this->loadedIds[] = $id;
            return $resource;
        });
        $resource->method('save')->willReturnCallback(function (Testimonial $model) use ($resource) {
            if ($this->saveException) {
                throw $this->saveException;
            }
            if (!$model->getId()) {
                $model->setId(42);
            }
            $this->saved = $model;
            return $resource;
        });

        return new Save($this->backendContext(), $factory, $resource);
    }

    public function testEmptyPostRedirectsToGrid(): void
    {
        $this->controller()->execute();

        $this->assertSame(['*/*/', []], $this->redirect);
        $this->assertNull($this->saved);
    }

    public function testNewTestimonialGetsGeneratedUrlKeyAndClampedRating(): void
    {
        $this->post = [
            'testimonial_id' => '',
            'customer_name' => 'Jane Doe',
            'title' => 'Great Service!',
            'rating' => '9',
        ];
        $this->controller()->execute();

        $this->assertSame([], $this->loadedIds);
        $this->assertSame('jane-doe-great-service', $this->saved->getUrlKey());
        $this->assertSame(5, $this->saved->getData('rating'));
        $this->assertSame(42, $this->saved->getData('testimonial_id'));
        $this->assertSame(['Testimonial saved successfully.'], $this->successMessages);
        $this->assertSame(['*/*/', []], $this->redirect);
    }

    public function testRatingBelowRangeIsRaisedToOne(): void
    {
        $this->post = ['customer_name' => 'A', 'title' => 'B', 'rating' => '-3'];
        $this->controller()->execute();

        $this->assertSame(1, $this->saved->getData('rating'));
    }

    public function testExistingTestimonialWithTakenUrlKeyGetsNumericSuffix(): void
    {
        $this->post = ['testimonial_id' => '7', 'url_key' => 'My Story', 'title' => 'x'];
        $this->params = ['back' => '1'];
        $this->fetchResults = ['3', '4', false];
        $this->controller()->execute();

        $this->assertSame([7], $this->loadedIds);
        $this->assertSame(['my-story', 'my-story-2', 'my-story-3'], $this->lookedUpKeys);
        $this->assertSame('my-story-3', $this->saved->getUrlKey());
        $this->assertSame(['*/*/edit', ['id' => '7']], $this->redirect);
    }

    public function testUrlKeyWithoutLatinCharactersFallsBackToDefault(): void
    {
        $this->post = ['url_key' => '!!!', 'title' => 'x'];
        $this->controller()->execute();

        $this->assertSame('testimonial', $this->saved->getUrlKey());
    }

    public function testSaveFailureShowsErrorAndReturnsToForm(): void
    {
        $this->post = ['testimonial_id' => '5', 'customer_name' => 'A', 'title' => 'B'];
        $this->saveException = new \RuntimeException('Duplicate entry');
        $this->controller()->execute();

        $this->assertSame(['Duplicate entry'], $this->errorMessages);
        $this->assertSame(['*/*/edit', ['id' => 5]], $this->redirect);
    }
}
