<?php
declare(strict_types=1);

namespace Panth\Testimonials\Test\Unit\Ui;

use Magento\Framework\Api\Filter;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Collection;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Panth\Testimonials\Block\Adminhtml\Category\Edit\DeleteButton as CategoryDeleteButton;
use Panth\Testimonials\Block\Adminhtml\Testimonial\Edit\DeleteButton as TestimonialDeleteButton;
use Panth\Testimonials\Model\Category\DataProvider as CategoryDataProvider;
use Panth\Testimonials\Model\ResourceModel\Category\Collection as CategoryCollection;
use Panth\Testimonials\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Panth\Testimonials\Model\ResourceModel\Testimonial\Collection as TestimonialCollection;
use Panth\Testimonials\Model\ResourceModel\Testimonial\CollectionFactory as TestimonialCollectionFactory;
use Panth\Testimonials\Model\Testimonial;
use Panth\Testimonials\Model\Testimonial\DataProvider as TestimonialDataProvider;
use Panth\Testimonials\Ui\Component\Listing\Column\Actions;
use Panth\Testimonials\Ui\Component\Listing\Column\CategoryActions;
use Panth\Testimonials\Ui\Component\Listing\LikeFulltextFilter;
use PHPUnit\Framework\TestCase;

class AdminUiTest extends TestCase
{
    private function urlBuilder(): UrlInterface
    {
        $url = $this->createStub(UrlInterface::class);
        $url->method('getUrl')->willReturnCallback(
            static fn($route, $params = []) => $route . '/id/' . ($params['id'] ?? '')
        );
        return $url;
    }

    public function testTestimonialActionsColumnAddsEditAndDeleteLinks(): void
    {
        $column = new Actions(
            $this->createStub(ContextInterface::class),
            $this->createStub(UiComponentFactory::class),
            $this->urlBuilder(),
            [],
            ['name' => 'actions']
        );

        $result = $column->prepareDataSource(['data' => ['items' => [['testimonial_id' => 5], ['testimonial_id' => 6]]]]);

        $this->assertSame('panth_testimonials/testimonial/edit/id/5', $result['data']['items'][0]['actions']['edit']['href']);
        $this->assertSame('panth_testimonials/testimonial/delete/id/6', $result['data']['items'][1]['actions']['delete']['href']);
        $this->assertTrue($result['data']['items'][0]['actions']['delete']['post']);
        $this->assertArrayHasKey('confirm', $result['data']['items'][0]['actions']['delete']);
    }

    public function testCategoryActionsColumnUsesCategoryRoutes(): void
    {
        $column = new CategoryActions(
            $this->createStub(ContextInterface::class),
            $this->createStub(UiComponentFactory::class),
            $this->urlBuilder(),
            [],
            ['name' => 'actions']
        );

        $result = $column->prepareDataSource(['data' => ['items' => [['category_id' => 2]]]]);

        $this->assertSame('panth_testimonials/category/edit/id/2', $result['data']['items'][0]['actions']['edit']['href']);
        $this->assertSame('panth_testimonials/category/delete/id/2', $result['data']['items'][0]['actions']['delete']['href']);
    }

    public function testActionsColumnLeavesDataSourceWithoutItemsUntouched(): void
    {
        $column = new Actions(
            $this->createStub(ContextInterface::class),
            $this->createStub(UiComponentFactory::class),
            $this->urlBuilder()
        );

        $this->assertSame(['data' => ['totalRecords' => 0]], $column->prepareDataSource(['data' => ['totalRecords' => 0]]));
    }

    private function filterCollection(?string &$where, ?array &$quoted): AbstractDb
    {
        $connection = $this->createStub(AdapterInterface::class);
        $connection->method('quoteInto')->willReturnCallback(function ($text, $value) use (&$quoted) {
            $quoted[] = $value;
            return str_replace('?', "'" . $value . "'", $text);
        });
        $select = $this->createStub(Select::class);
        $select->method('where')->willReturnCallback(function ($cond) use (&$where, $select) {
            $where = $cond;
            return $select;
        });
        $collection = $this->createStub(AbstractDb::class);
        $collection->method('getConnection')->willReturn($connection);
        $collection->method('getSelect')->willReturn($select);
        return $collection;
    }

    public function testLikeFilterBuildsEscapedOrConditionAcrossDefaultColumns(): void
    {
        $where = null;
        $quoted = [];
        $collection = $this->filterCollection($where, $quoted);

        (new LikeFulltextFilter())->apply($collection, new Filter(['value' => ' 50%_off ']));

        $this->assertSame(array_fill(0, 4, '%50\%\_off%'), $quoted);
        $this->assertStringContainsString("main_table.customer_name LIKE '%50\\%\\_off%'", $where);
        $this->assertSame(3, substr_count($where, ' OR '));
    }

    public function testLikeFilterUsesConfiguredColumnsAndIgnoresNonStrings(): void
    {
        $where = null;
        $quoted = [];
        $collection = $this->filterCollection($where, $quoted);

        (new LikeFulltextFilter(['main_table.title', 7]))->apply($collection, new Filter(['value' => 'abc']));

        $this->assertSame("main_table.title LIKE '%abc%'", $where);
    }

    public function testLikeFilterSkipsEmptyOrNonScalarValues(): void
    {
        $where = null;
        $quoted = [];
        $collection = $this->filterCollection($where, $quoted);
        $filter = new LikeFulltextFilter();

        $filter->apply($collection, new Filter(['value' => '   ']));
        $filter->apply($collection, new Filter(['value' => ['x']]));
        $filter->apply($this->createStub(Collection::class), new Filter(['value' => 'abc']));

        $this->assertNull($where);
        $this->assertSame([], $quoted);
    }

    public function testLikeFilterTruncatesLongSearchTerms(): void
    {
        $where = null;
        $quoted = [];
        $collection = $this->filterCollection($where, $quoted);

        (new LikeFulltextFilter(['c']))->apply($collection, new Filter(['value' => str_repeat('a', 500)]));

        $this->assertSame('%' . str_repeat('a', 200) . '%', $quoted[0]);
    }

    public function testDeleteButtonsOnlyShowForExistingRecords(): void
    {
        foreach ([TestimonialDeleteButton::class, CategoryDeleteButton::class] as $class) {
            $request = $this->createStub(RequestInterface::class);
            $request->method('getParam')->willReturn(null);
            $this->assertSame([], (new $class($request, $this->urlBuilder()))->getButtonData());

            $request = $this->createStub(RequestInterface::class);
            $request->method('getParam')->willReturn('9');
            $data = (new $class($request, $this->urlBuilder()))->getButtonData();
            $this->assertSame('delete', $data['class']);
            $this->assertStringContainsString('*/*/delete/id/9', $data['on_click']);
            $this->assertSame(20, $data['sort_order']);
        }
    }

    public function testTestimonialDataProviderMergesPersistedDataOnce(): void
    {
        $existing = (new \ReflectionClass(Testimonial::class))->newInstanceWithoutConstructor();
        $existing->setData(['id' => 4, 'title' => 'Saved']);
        $empty = (new \ReflectionClass(Testimonial::class))->newInstanceWithoutConstructor();

        $collection = $this->createStub(TestimonialCollection::class);
        $collection->method('getItems')->willReturn([$existing]);
        $collection->method('getNewEmptyItem')->willReturn($empty);
        $factory = $this->createStub(TestimonialCollectionFactory::class);
        $factory->method('create')->willReturn($collection);

        $persistor = $this->createMock(DataPersistorInterface::class);
        $persistor->expects($this->once())->method('get')->with('panth_testimonial')
            ->willReturn(['id' => 7, 'title' => 'Unsaved draft']);
        $persistor->expects($this->once())->method('clear')->with('panth_testimonial');

        $provider = new TestimonialDataProvider('ds', 'testimonial_id', 'id', $factory, $persistor);
        $data = $provider->getData();

        $this->assertSame('Saved', $data[4]['title']);
        $this->assertSame('Unsaved draft', $data[7]['title']);
        $this->assertSame($data, $provider->getData());
    }

    public function testCategoryDataProviderReturnsCollectionRows(): void
    {
        $item = (new \ReflectionClass(\Panth\Testimonials\Model\Category::class))->newInstanceWithoutConstructor();
        $item->setData(['id' => 2, 'name' => 'Support']);
        $collection = $this->createStub(CategoryCollection::class);
        $collection->method('getItems')->willReturn([$item]);
        $factory = $this->createStub(CategoryCollectionFactory::class);
        $factory->method('create')->willReturn($collection);
        $persistor = $this->createMock(DataPersistorInterface::class);
        $persistor->expects($this->once())->method('get')->with('panth_testimonial_category')->willReturn(null);
        $persistor->expects($this->never())->method('clear');

        $data = (new CategoryDataProvider('ds', 'category_id', 'id', $factory, $persistor))->getData();

        $this->assertSame([2 => ['id' => 2, 'name' => 'Support']], $data);
    }
}
