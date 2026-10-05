<?php
declare(strict_types=1);

namespace Panth\Testimonials\Test\Unit\Block;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Panth\Testimonials\Model\Category;
use Panth\Testimonials\Model\Testimonial;
use PHPUnit\Framework\TestCase;

/**
 * Builds a template context whose request params, config values and store are driven by test properties.
 */
abstract class BlockTestCase extends TestCase
{
    protected array $params = [];
    protected array $config = [];
    protected array $flags = [];
    protected Store $store;
    protected StoreManagerInterface $storeManager;

    protected function setUp(): void
    {
        $this->store = $this->createStub(Store::class);
        $this->store->method('getId')->willReturn(2);
        $this->store->method('getBaseUrl')->willReturnCallback(
            static fn($type = UrlInterface::URL_TYPE_LINK) => $type === UrlInterface::URL_TYPE_MEDIA
                ? 'https://shop.test/media/'
                : 'https://shop.test/'
        );
        $this->storeManager = $this->createStub(StoreManagerInterface::class);
        $this->storeManager->method('getStore')->willReturn($this->store);
    }

    protected function context(): Context
    {
        $request = $this->createStub(Http::class);
        $request->method('getParam')->willReturnCallback(
            fn(string $key, $default = null) => $this->params[$key] ?? $default
        );

        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(fn(string $path) => $this->config[$path] ?? null);
        $scopeConfig->method('isSetFlag')->willReturnCallback(fn(string $path) => (bool) ($this->flags[$path] ?? false));

        $urlBuilder = $this->createStub(UrlInterface::class);
        $urlBuilder->method('getBaseUrl')->willReturn('https://shop.test/');
        $urlBuilder->method('getUrl')->willReturnCallback(
            static fn($route = '', $params = []) => 'https://shop.test/' . $route
        );

        $context = $this->createStub(Context::class);
        $context->method('getRequest')->willReturn($request);
        $context->method('getScopeConfig')->willReturn($scopeConfig);
        $context->method('getStoreManager')->willReturn($this->storeManager);
        $context->method('getUrlBuilder')->willReturn($urlBuilder);

        return $context;
    }

    protected function testimonial(array $data = []): Testimonial
    {
        $model = (new \ReflectionClass(Testimonial::class))->newInstanceWithoutConstructor();
        $model->setIdFieldName('testimonial_id');
        $model->setData($data);
        return $model;
    }

    protected function category(array $data = []): Category
    {
        $model = (new \ReflectionClass(Category::class))->newInstanceWithoutConstructor();
        $model->setIdFieldName('category_id');
        $model->setData($data);
        return $model;
    }
}
