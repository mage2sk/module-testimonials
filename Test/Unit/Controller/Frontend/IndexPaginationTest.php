<?php
declare(strict_types=1);

namespace Panth\Testimonials\Test\Unit\Controller\Frontend;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Forward;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Title;
use Magento\Framework\View\Result\Page;
use Panth\Testimonials\Block\Testimonials;
use Panth\Testimonials\Controller\Index\Index;
use Panth\Testimonials\Helper\Data;
use Panth\Testimonials\Model\ResourceModel\Testimonial\Collection;
use PHPUnit\Framework\TestCase;

class IndexPaginationTest extends TestCase
{
    private ?array $forwardedTo = null;

    private function dispatch(int|string|null $page, int $lastPage): Page|Forward
    {
        $request = $this->createStub(RequestInterface::class);
        $request->method('getParam')->willReturnCallback(
            fn(string $key, $default = null) => $key === 'p' && $page !== null ? $page : $default
        );
        $collection = $this->createStub(Collection::class);
        $collection->method('getLastPageNumber')->willReturn($lastPage);
        $block = $this->createStub(Testimonials::class);
        $block->method('getRequest')->willReturn($request);
        $block->method('getTestimonials')->willReturn($collection);

        $layout = $this->createStub(LayoutInterface::class);
        $layout->method('getBlock')->willReturnCallback(
            fn(string $name) => $name === 'testimonials.listing' ? $block : false
        );
        $config = $this->createStub(Config::class);
        $config->method('getTitle')->willReturn($this->createStub(Title::class));
        $resultPage = $this->createStub(Page::class);
        $resultPage->method('getLayout')->willReturn($layout);
        $resultPage->method('getConfig')->willReturn($config);

        $forward = $this->createStub(Forward::class);
        $state = [];
        $forward->method('setModule')->willReturnCallback(function ($m) use (&$state, $forward) {
            $state[0] = $m;
            return $forward;
        });
        $forward->method('setController')->willReturnCallback(function ($c) use (&$state, $forward) {
            $state[1] = $c;
            return $forward;
        });
        $forward->method('forward')->willReturnCallback(function ($a) use (&$state, $forward) {
            $this->forwardedTo = [$state[0] ?? null, $state[1] ?? null, $a];
            return $forward;
        });

        $factory = $this->createStub(ResultFactory::class);
        $factory->method('create')->willReturnCallback(
            fn(string $type) => $type === ResultFactory::TYPE_FORWARD ? $forward : $resultPage
        );
        $helper = $this->createStub(Data::class);
        $helper->method('isEnabled')->willReturn(true);
        $helper->method('getPageTitle')->willReturn('Customer Testimonials');
        $helper->method('getMetaDescription')->willReturn('');

        return (new Index($factory, $helper))->execute();
    }

    public function testPageBeyondLastPageForwardsToNoRoute(): void
    {
        $result = $this->dispatch('99', 3);

        $this->assertInstanceOf(Forward::class, $result);
        $this->assertSame(['cms', 'noroute', 'index'], $this->forwardedTo);
    }

    public function testExistingPagesRender(): void
    {
        foreach ([[null, 3], ['1', 3], ['3', 3], ['2', 3]] as [$page, $last]) {
            $this->forwardedTo = null;
            $this->assertInstanceOf(Page::class, $this->dispatch($page, $last));
            $this->assertNull($this->forwardedTo);
        }
    }

    public function testFirstPageRendersEvenWhenListIsEmpty(): void
    {
        $this->assertInstanceOf(Page::class, $this->dispatch('1', 0));
        $this->assertNull($this->forwardedTo);
    }

    public function testSecondPageOfEmptyListForwards(): void
    {
        $this->assertInstanceOf(Forward::class, $this->dispatch('2', 1));
    }
}
