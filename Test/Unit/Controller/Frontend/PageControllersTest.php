<?php
declare(strict_types=1);

namespace Panth\Testimonials\Test\Unit\Controller\Frontend;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Forward;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Title;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Theme\Block\Html\Breadcrumbs;
use Panth\Testimonials\Controller\Category\View as CategoryView;
use Panth\Testimonials\Controller\Index\Index as ListIndex;
use Panth\Testimonials\Controller\Submit\Index as SubmitIndex;
use Panth\Testimonials\Controller\View\Index as TestimonialView;
use Panth\Testimonials\Helper\Data;
use Panth\Testimonials\Model\Category;
use Panth\Testimonials\Model\ResourceModel\Category\Collection as CategoryCollection;
use Panth\Testimonials\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Panth\Testimonials\Model\ResourceModel\Testimonial\Collection as TestimonialCollection;
use Panth\Testimonials\Model\ResourceModel\Testimonial\CollectionFactory as TestimonialCollectionFactory;
use Panth\Testimonials\Model\Testimonial;
use PHPUnit\Framework\TestCase;

class PageControllersTest extends TestCase
{
    private bool $enabled = true;
    private bool $submitEnabled = true;
    private array $params = [];
    private ?string $title = null;
    private ?string $description = null;
    private array $crumbs = [];
    private ?array $forwardedTo = null;
    private Page $page;
    private Forward $forward;
    private Data $helper;

    protected function setUp(): void
    {
        $titleObj = $this->createStub(Title::class);
        $titleObj->method('set')->willReturnCallback(function ($t) {
            $this->title = (string) $t;
        });
        $config = $this->createStub(Config::class);
        $config->method('getTitle')->willReturn($titleObj);
        $config->method('setDescription')->willReturnCallback(function ($d) {
            $this->description = (string) $d;
        });
        $breadcrumbs = $this->createStub(Breadcrumbs::class);
        $breadcrumbs->method('addCrumb')->willReturnCallback(function ($name, $info) use ($breadcrumbs) {
            $this->crumbs[$name] = $info;
            return $breadcrumbs;
        });
        $layout = $this->createStub(LayoutInterface::class);
        $layout->method('getBlock')->willReturn($breadcrumbs);
        $this->page = $this->createStub(Page::class);
        $this->page->method('getConfig')->willReturn($config);
        $this->page->method('getLayout')->willReturn($layout);

        $this->forward = $this->createStub(Forward::class);
        $module = null;
        $controller = null;
        $this->forward->method('setModule')->willReturnCallback(function ($m) use (&$module) {
            $module = $m;
            return $this->forward;
        });
        $this->forward->method('setController')->willReturnCallback(function ($c) use (&$controller) {
            $controller = $c;
            return $this->forward;
        });
        $this->forward->method('forward')->willReturnCallback(function ($a) use (&$module, &$controller) {
            $this->forwardedTo = [$module, $controller, $a];
            return $this->forward;
        });

        $this->helper = $this->createStub(Data::class);
        $this->helper->method('isEnabled')->willReturnCallback(fn() => $this->enabled);
        $this->helper->method('isSubmitEnabled')->willReturnCallback(fn() => $this->submitEnabled);
        $this->helper->method('getPageTitle')->willReturn('Customer Testimonials');
        $this->helper->method('getMetaDescription')->willReturn('Meta');
        $this->helper->method('getBaseUrl')->willReturn('testimonials');
        $this->helper->method('getCategoryMetaDescription')->willReturnCallback(
            static fn($name, $desc) => $desc ?: 'About ' . $name
        );
    }

    private function resultFactory(): ResultFactory
    {
        $factory = $this->createStub(ResultFactory::class);
        $factory->method('create')->willReturnCallback(
            fn(string $type) => $type === ResultFactory::TYPE_FORWARD ? $this->forward : $this->page
        );
        return $factory;
    }

    private function forwardFactory(): ForwardFactory
    {
        $factory = $this->createStub(ForwardFactory::class);
        $factory->method('create')->willReturn($this->forward);
        return $factory;
    }

    private function pageFactory(): PageFactory
    {
        $factory = $this->createStub(PageFactory::class);
        $factory->method('create')->willReturn($this->page);
        return $factory;
    }

    private function actionContext(): Context
    {
        $request = $this->createStub(Http::class);
        $request->method('getParam')->willReturnCallback(
            fn(string $key, $default = null) => $this->params[$key] ?? $default
        );
        $context = $this->createStub(Context::class);
        $context->method('getRequest')->willReturn($request);
        return $context;
    }

    private function storeManager(): StoreManagerInterface
    {
        $store = $this->createStub(StoreInterface::class);
        $store->method('getId')->willReturn(1);
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);
        return $storeManager;
    }

    private function testimonialCollectionFactory(?Testimonial $item): TestimonialCollectionFactory
    {
        $collection = $this->createStub(TestimonialCollection::class);
        $collection->method('addFieldToFilter')->willReturnSelf();
        $collection->method('addStoreFilter')->willReturnSelf();
        $collection->method('setPageSize')->willReturnSelf();
        $collection->method('getFirstItem')->willReturn(
            $item ?? (new \ReflectionClass(Testimonial::class))->newInstanceWithoutConstructor()
        );
        $factory = $this->createStub(TestimonialCollectionFactory::class);
        $factory->method('create')->willReturn($collection);
        return $factory;
    }

    private function categoryCollectionFactory(?Category $item): CategoryCollectionFactory
    {
        $collection = $this->createStub(CategoryCollection::class);
        $collection->method('addFieldToFilter')->willReturnSelf();
        $collection->method('addStoreFilter')->willReturnSelf();
        $collection->method('setPageSize')->willReturnSelf();
        $collection->method('getFirstItem')->willReturn(
            $item ?? (new \ReflectionClass(Category::class))->newInstanceWithoutConstructor()
        );
        $factory = $this->createStub(CategoryCollectionFactory::class);
        $factory->method('create')->willReturn($collection);
        return $factory;
    }

    public function testListPageForwardsToNoRouteWhenDisabled(): void
    {
        $this->enabled = false;

        $result = (new ListIndex($this->resultFactory(), $this->helper))->execute();

        $this->assertSame($this->forward, $result);
        $this->assertSame(['cms', 'noroute', 'index'], $this->forwardedTo);
    }

    public function testListPageSetsTitleMetaAndBreadcrumbs(): void
    {
        $result = (new ListIndex($this->resultFactory(), $this->helper))->execute();

        $this->assertSame($this->page, $result);
        $this->assertSame('Customer Testimonials', $this->title);
        $this->assertSame('Meta', $this->description);
        $this->assertSame(['home', 'testimonials'], array_keys($this->crumbs));
    }

    public function testSubmitPageRequiresSubmissionEnabled(): void
    {
        $this->submitEnabled = false;

        $result = (new SubmitIndex($this->resultFactory(), $this->forwardFactory(), $this->helper))->execute();

        $this->assertSame($this->forward, $result);
        $this->assertSame(['cms', 'noroute', 'index'], $this->forwardedTo);
    }

    public function testSubmitPageRenders(): void
    {
        $result = (new SubmitIndex($this->resultFactory(), $this->forwardFactory(), $this->helper))->execute();

        $this->assertSame($this->page, $result);
        $this->assertSame('Submit a Testimonial', $this->title);
        $this->assertSame('/testimonials', $this->crumbs['testimonials']['link']);
        $this->assertArrayHasKey('submit', $this->crumbs);
    }

    private function testimonialView(?Testimonial $item): TestimonialView
    {
        return new TestimonialView(
            $this->actionContext(),
            $this->pageFactory(),
            $this->forwardFactory(),
            $this->testimonialCollectionFactory($item),
            $this->helper,
            $this->storeManager()
        );
    }

    public function testTestimonialViewWithoutUrlKeyIs404(): void
    {
        $this->assertSame($this->forward, $this->testimonialView(null)->execute());
        $this->assertSame(['cms', 'noroute', 'index'], $this->forwardedTo);
    }

    public function testTestimonialViewUnknownUrlKeyIs404(): void
    {
        $this->params = ['url_key' => 'missing'];

        $this->assertSame($this->forward, $this->testimonialView(null)->execute());
    }

    public function testTestimonialViewUsesMetaTitleAndDescription(): void
    {
        $this->params = ['url_key' => 'great'];
        $item = (new \ReflectionClass(Testimonial::class))->newInstanceWithoutConstructor();
        $item->setData(['id' => 3, 'title' => 'Great', 'meta_title' => 'Meta Great', 'meta_description' => 'Desc']);

        $this->assertSame($this->page, $this->testimonialView($item)->execute());
        $this->assertSame('Meta Great', $this->title);
        $this->assertSame('Desc', $this->description);
        $this->assertSame('Great', $this->crumbs['testimonial']['label']);
    }

    public function testTestimonialViewFallsBackToTitleWithoutMeta(): void
    {
        $this->params = ['url_key' => 'great'];
        $item = (new \ReflectionClass(Testimonial::class))->newInstanceWithoutConstructor();
        $item->setData(['id' => 3, 'title' => 'Great']);

        $this->testimonialView($item)->execute();

        $this->assertSame('Great', $this->title);
        $this->assertNull($this->description);
    }

    private function categoryView(?Category $item): CategoryView
    {
        return new CategoryView(
            $this->actionContext(),
            $this->pageFactory(),
            $this->forwardFactory(),
            $this->categoryCollectionFactory($item),
            $this->helper,
            $this->storeManager()
        );
    }

    public function testCategoryViewDisabledIs404(): void
    {
        $this->enabled = false;
        $this->params = ['url_key' => 'support'];

        $this->assertSame($this->forward, $this->categoryView(null)->execute());
    }

    public function testCategoryViewUnknownCategoryIs404(): void
    {
        $this->params = ['url_key' => 'missing'];

        $this->assertSame($this->forward, $this->categoryView(null)->execute());
    }

    public function testCategoryViewSetsTitleAndMeta(): void
    {
        $this->params = ['url_key' => 'support'];
        $category = (new \ReflectionClass(Category::class))->newInstanceWithoutConstructor();
        $category->setData(['id' => 4, 'name' => 'Support']);

        $this->assertSame($this->page, $this->categoryView($category)->execute());
        $this->assertSame('Support - Customer Testimonials', $this->title);
        $this->assertSame('About Support', $this->description);
        $this->assertSame('Support', $this->crumbs['category']['label']);
    }
}
