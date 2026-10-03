<?php
declare(strict_types=1);

namespace Panth\Testimonials\Test\Unit\Block;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\Website;
use Panth\Testimonials\Block\Schema;
use Panth\Testimonials\Model\ResourceModel\Testimonial\Collection;
use Panth\Testimonials\Model\ResourceModel\Testimonial\CollectionFactory;

class SchemaTest extends BlockTestCase
{
    private array $items = [];
    private array $filters = [];

    private function block(): Schema
    {
        $collection = $this->createStub(Collection::class);
        $collection->method('addFieldToFilter')->willReturnCallback(function ($field, $value) use ($collection) {
            $this->filters[$field] = $value;
            return $collection;
        });
        $collection->method('setOrder')->willReturnSelf();
        $collection->method('setPageSize')->willReturnSelf();
        $collection->method('getIterator')->willReturnCallback(fn() => new \ArrayIterator($this->items));
        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);

        return new Schema($this->context(), $factory, $this->storeManager, new Json());
    }

    private function decoded(): array
    {
        $json = $this->block()->getSchemaJson();
        $this->assertNotSame('', $json);
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    public function testEmptyCollectionProducesNoSchema(): void
    {
        $this->assertSame('', $this->block()->getSchemaJson());
        $this->assertSame(1, $this->filters['status']);
        $this->assertSame(['in' => [0, 2]], $this->filters['store_id']);
    }

    public function testStoreFailureProducesNoSchema(): void
    {
        $this->storeManager = $this->createStub(\Magento\Store\Model\StoreManagerInterface::class);
        $this->storeManager->method('getStore')->willThrowException(new \RuntimeException('no store'));

        $this->assertSame('', $this->block()->getSchemaJson());
    }

    public function testItemListContainsReviewsWithConfiguredItemReviewed(): void
    {
        $this->config = [
            'panth_testimonials/schema/item_reviewed_type' => 'LocalBusiness',
            'panth_testimonials/schema/item_reviewed_name' => 'Acme Store',
            'panth_testimonials/schema/item_reviewed_id' => '/#org',
        ];
        $this->items = [
            $this->testimonial([
                'customer_name' => 'Jane',
                'content' => 'Loved it',
                'title' => 'Great',
                'rating' => 4,
                'created_at' => '2026-01-15 10:00:00',
                'customer_company' => 'Acme',
                'customer_title' => 'CTO',
            ]),
            $this->testimonial(['customer_name' => 'Bob', 'rating' => 5, 'created_at' => '2026-02-01']),
        ];

        $schema = $this->decoded();

        $this->assertSame('ItemList', $schema['@type']);
        $this->assertSame('https://shop.test/#testimonials', $schema['@id']);
        $this->assertSame(2, $schema['numberOfItems']);
        $first = $schema['itemListElement'][0];
        $this->assertSame(1, $first['position']);
        $this->assertSame(2, $schema['itemListElement'][1]['position']);
        $this->assertSame('Review', $first['item']['@type']);
        $this->assertSame('Jane', $first['item']['author']['name']);
        $this->assertSame('Acme', $first['item']['author']['affiliation']['name']);
        $this->assertSame('CTO', $first['item']['author']['jobTitle']);
        $this->assertEquals(4, $first['item']['reviewRating']['ratingValue']);
        $this->assertSame('2026-01-15', $first['item']['datePublished']);
        $this->assertSame(
            ['@type' => 'LocalBusiness', 'name' => 'Acme Store', '@id' => 'https://shop.test/#org'],
            $first['item']['itemReviewed']
        );
        $this->assertArrayNotHasKey('affiliation', $schema['itemListElement'][1]['item']['author']);
        $this->assertArrayNotHasKey('jobTitle', $schema['itemListElement'][1]['item']['author']);
    }

    public function testItemReviewedDefaultsToOrganizationAndStoreInformationName(): void
    {
        $this->config = [
            'general/store_information/name' => 'Store Info Name',
            'panth_testimonials/schema/item_reviewed_id' => 'https://other.test/#org',
        ];
        $this->items = [$this->testimonial(['customer_name' => 'Jane', 'created_at' => '2026-01-01'])];

        $item = $this->decoded()['itemListElement'][0]['item']['itemReviewed'];

        $this->assertSame(
            ['@type' => 'Organization', 'name' => 'Store Info Name', '@id' => 'https://other.test/#org'],
            $item
        );
    }

    public function testItemReviewedNameFallsBackToFrontendThenWebsiteName(): void
    {
        $this->store->method('getFrontendName')->willReturn('');
        $this->store->method('getWebsiteId')->willReturn(1);
        $website = $this->createStub(Website::class);
        $website->method('getName')->willReturn('Main Website');
        $this->storeManager->method('getWebsite')->willReturn($website);
        $this->items = [$this->testimonial(['customer_name' => 'Jane', 'created_at' => '2026-01-01'])];

        $item = $this->decoded()['itemListElement'][0]['item']['itemReviewed'];

        $this->assertSame('Main Website', $item['name']);
        $this->assertArrayNotHasKey('@id', $item);
    }

    public function testSchemaUrlUsesConfiguredRoute(): void
    {
        $this->config = ['panth_testimonials/general/route' => '/reviews/'];
        $this->items = [$this->testimonial(['customer_name' => 'Jane', 'created_at' => '2026-01-01'])];

        $this->assertSame('https://shop.test/reviews', $this->decoded()['url']);
    }

    public function testSchemaUrlFallsBackToDefaultRoute(): void
    {
        $this->items = [$this->testimonial(['customer_name' => 'Jane', 'created_at' => '2026-01-01'])];

        $this->assertSame('https://shop.test/testimonials', $this->decoded()['url']);
    }
}
