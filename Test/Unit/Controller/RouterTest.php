<?php
declare(strict_types=1);

namespace Panth\Testimonials\Test\Unit\Controller;

use Magento\Framework\App\Action\Forward;
use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\Request\Http;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Panth\Testimonials\Controller\Router;
use Panth\Testimonials\Helper\Data;
use Panth\Testimonials\Model\ResourceModel\Category\Collection as CategoryCollection;
use Panth\Testimonials\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Panth\Testimonials\Model\ResourceModel\Testimonial\Collection as TestimonialCollection;
use Panth\Testimonials\Model\ResourceModel\Testimonial\CollectionFactory as TestimonialCollectionFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class RouterTest extends TestCase
{
    private Forward $forward;
    private int $categorySize = 0;
    private int $testimonialSize = 0;
    private int $lookups = 0;

    protected function setUp(): void
    {
        $this->forward = $this->createStub(Forward::class);
    }

    private function router(bool $enabled = true, string $route = 'testimonials'): Router
    {
        $helper = $this->createStub(Data::class);
        $helper->method('isEnabled')->willReturn($enabled);
        $helper->method('getBaseUrl')->willReturn($route);

        $actionFactory = $this->createStub(ActionFactory::class);
        $actionFactory->method('create')->willReturn($this->forward);

        $categoryCollection = $this->createStub(CategoryCollection::class);
        $categoryCollection->method('addFieldToFilter')->willReturnSelf();
        $categoryCollection->method('addStoreFilter')->willReturnSelf();
        $categoryCollection->method('setPageSize')->willReturnSelf();
        $categoryCollection->method('getSize')->willReturnCallback(fn() => $this->categorySize);
        $categoryFactory = $this->createStub(CategoryCollectionFactory::class);
        $categoryFactory->method('create')->willReturnCallback(function () use ($categoryCollection) {
            $this->lookups++;
            return $categoryCollection;
        });

        $testimonialCollection = $this->createStub(TestimonialCollection::class);
        $testimonialCollection->method('addFieldToFilter')->willReturnSelf();
        $testimonialCollection->method('addStoreFilter')->willReturnSelf();
        $testimonialCollection->method('setPageSize')->willReturnSelf();
        $testimonialCollection->method('getSize')->willReturnCallback(fn() => $this->testimonialSize);
        $testimonialFactory = $this->createStub(TestimonialCollectionFactory::class);
        $testimonialFactory->method('create')->willReturnCallback(function () use ($testimonialCollection) {
            $this->lookups++;
            return $testimonialCollection;
        });

        $store = $this->createStub(StoreInterface::class);
        $store->method('getId')->willReturn(1);
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        return new Router($actionFactory, $helper, $categoryFactory, $testimonialFactory, $storeManager);
    }

    private function request(string $path): Http
    {
        $request = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getPathInfo'])
            ->getMock();
        $request->method('getPathInfo')->willReturn($path);
        return $request;
    }

    public function testDisabledModuleNeverMatches(): void
    {
        $this->assertNull($this->router(false)->match($this->request('/testimonials')));
    }

    public function testBaseRouteForwardsToIndex(): void
    {
        $request = $this->request('/testimonials/');

        $this->assertSame($this->forward, $this->router()->match($request));
        $this->assertSame(['testimonials', 'index', 'index'], [
            $request->getModuleName(), $request->getControllerName(), $request->getActionName(),
        ]);
    }

    public function testCustomRouteIsHonoured(): void
    {
        $request = $this->request('/reviews');

        $this->assertSame($this->forward, $this->router(true, 'reviews')->match($request));
        $this->assertNull($this->router(true, 'reviews')->match($this->request('/testimonials')));
    }

    public function testUnrelatedPathIsIgnored(): void
    {
        $this->assertNull($this->router()->match($this->request('/testimonialsx/foo')));
        $this->assertSame(0, $this->lookups);
    }

    public function testPaginationSetsPageParam(): void
    {
        $request = $this->request('/testimonials/page/3');

        $this->assertSame($this->forward, $this->router()->match($request));
        $this->assertSame('index', $request->getControllerName());
        $this->assertSame(3, $request->getParam('p'));
    }

    public function testNonNumericPageFallsThroughToSlugLookup(): void
    {
        $this->assertNull($this->router()->match($this->request('/testimonials/page/abc')));
    }

    public function testSubmitPageForwardsToSubmitController(): void
    {
        $request = $this->request('/testimonials/submit');

        $this->assertSame($this->forward, $this->router()->match($request));
        $this->assertSame(['submit', 'index'], [$request->getControllerName(), $request->getActionName()]);
    }

    #[DataProvider('reservedPaths')]
    public function testReservedPathsAreLeftToStandardRouter(string $path): void
    {
        $this->assertNull($this->router()->match($this->request($path)));
        $this->assertSame(0, $this->lookups);
    }

    public static function reservedPaths(): array
    {
        return [
            'submit save' => ['/testimonials/submit/save'],
            'view action' => ['/testimonials/foo/view'],
            'delete action' => ['/testimonials/foo/delete'],
            'edit action' => ['/testimonials/foo/edit'],
        ];
    }

    public function testCategoryPathSetsUrlKey(): void
    {
        $request = $this->request('/testimonials/category/shipping');

        $this->assertSame($this->forward, $this->router()->match($request));
        $this->assertSame(['category', 'view'], [$request->getControllerName(), $request->getActionName()]);
        $this->assertSame('shipping', $request->getParam('url_key'));
    }

    public function testSlugMatchingActiveCategoryRoutesToCategory(): void
    {
        $this->categorySize = 1;
        $request = $this->request('/testimonials/shipping');

        $this->assertSame($this->forward, $this->router()->match($request));
        $this->assertSame('category', $request->getControllerName());
        $this->assertSame('shipping', $request->getParam('url_key'));
        $this->assertSame(1, $this->lookups, 'testimonial lookup is skipped when a category matches');
    }

    public function testSlugMatchingApprovedTestimonialRoutesToView(): void
    {
        $this->testimonialSize = 1;
        $request = $this->request('/testimonials/great-service');

        $this->assertSame($this->forward, $this->router()->match($request));
        $this->assertSame(['view', 'index'], [$request->getControllerName(), $request->getActionName()]);
        $this->assertSame('great-service', $request->getParam('url_key'));
    }

    public function testUnknownSlugDoesNotMatch(): void
    {
        $this->assertNull($this->router()->match($this->request('/testimonials/missing')));
        $this->assertSame(2, $this->lookups);
    }

    public function testDeepUnknownPathDoesNotMatch(): void
    {
        $this->assertNull($this->router()->match($this->request('/testimonials/a/b/c')));
        $this->assertSame(0, $this->lookups);
    }
}
