<?php
declare(strict_types=1);

namespace Panth\Testimonials\Test\Unit\Controller\Adminhtml;

use Panth\Testimonials\Controller\Adminhtml\Category\Save;
use Panth\Testimonials\Model\Category;
use Panth\Testimonials\Model\CategoryFactory;
use Panth\Testimonials\Model\ResourceModel\Category as CategoryResource;

class CategorySaveTest extends AdminControllerTestCase
{
    private ?Category $saved = null;
    private ?\Throwable $saveException = null;

    private function controller(): Save
    {
        $factory = $this->createStub(CategoryFactory::class);
        $factory->method('create')->willReturnCallback(
            function () {
                $model = (new \ReflectionClass(Category::class))->newInstanceWithoutConstructor();
                $model->setIdFieldName('category_id');
                return $model;
            }
        );

        $resource = $this->createStub(CategoryResource::class);
        $resource->method('getConnection')->willReturn($this->connection());
        $resource->method('getMainTable')->willReturn('panth_testimonial_category');
        $resource->method('save')->willReturnCallback(function (Category $model) use ($resource) {
            if ($this->saveException) {
                throw $this->saveException;
            }
            $model->setId($model->getId() ?: 11);
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

    public function testUrlKeyIsDerivedFromNameForNewCategory(): void
    {
        $this->post = ['category_id' => '', 'name' => ' Fast  Shipping & Delivery '];
        $this->params = ['back' => '1'];
        $this->controller()->execute();

        $this->assertSame('fast-shipping-delivery', $this->saved->getUrlKey());
        $this->assertSame(11, $this->saved->getData('category_id'), 'blank posted id is dropped before save');
        $this->assertSame(['Category saved.'], $this->successMessages);
        $this->assertSame(['*/*/edit', ['id' => 11]], $this->redirect);
    }

    public function testDuplicateUrlKeyIsSuffixed(): void
    {
        $this->post = ['category_id' => '4', 'name' => 'Support', 'url_key' => 'support'];
        $this->fetchResults = ['9', false];
        $this->controller()->execute();

        $this->assertSame('support-2', $this->saved->getUrlKey());
        $this->assertSame(['*/*/', []], $this->redirect);
    }

    public function testEmptyUrlKeyFallsBackToCategory(): void
    {
        $this->post = ['name' => '***'];
        $this->controller()->execute();

        $this->assertSame('category', $this->saved->getUrlKey());
    }

    public function testFailureReturnsToEditForm(): void
    {
        $this->post = ['category_id' => '4', 'name' => 'Support'];
        $this->saveException = new \RuntimeException('boom');
        $this->controller()->execute();

        $this->assertSame(['boom'], $this->errorMessages);
        $this->assertSame(['*/*/edit', ['id' => 4]], $this->redirect);
    }
}
