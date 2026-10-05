<?php
declare(strict_types=1);

namespace Panth\Testimonials\Test\Unit\Model\Config\Source;

use Magento\Framework\DataObject;
use Panth\Testimonials\Model\Config\Source\CategoryList;
use Panth\Testimonials\Model\Config\Source\ItemReviewedType;
use Panth\Testimonials\Model\Config\Source\Status;
use Panth\Testimonials\Model\ResourceModel\Category\Collection;
use Panth\Testimonials\Model\ResourceModel\Category\CollectionFactory;
use Panth\Testimonials\Model\Testimonial;
use PHPUnit\Framework\TestCase;

class SourceModelsTest extends TestCase
{
    public function testStatusOptionsMatchModelConstants(): void
    {
        $status = new Status();
        $expected = [Testimonial::STATUS_PENDING, Testimonial::STATUS_APPROVED, Testimonial::STATUS_REJECTED];

        $this->assertSame($expected, array_column($status->toOptionArray(), 'value'));
        $this->assertSame($expected, array_keys($status->toArray()));
        $this->assertSame('Approved', (string) $status->toArray()[Testimonial::STATUS_APPROVED]);
    }

    public function testItemReviewedTypeOffersSchemaOrgTypes(): void
    {
        $values = array_column((new ItemReviewedType())->toOptionArray(), 'value');

        $this->assertSame(['Organization', 'LocalBusiness', 'ProfessionalService', 'Product', 'Service'], $values);
    }

    public function testCategoryListPrependsAllOptionAndIsMemoized(): void
    {
        $collection = $this->createMock(Collection::class);
        $collection->expects($this->once())->method('addActiveFilter')->willReturnSelf();
        $collection->expects($this->once())->method('addDefaultOrder')->willReturnSelf();
        $collection->method('getIterator')->willReturn(new \ArrayIterator([
            new DataObject(['id' => 3, 'name' => 'Service']),
            new DataObject(['id' => 7, 'name' => 'Support']),
        ]));

        $factory = $this->createMock(CollectionFactory::class);
        $factory->expects($this->once())->method('create')->willReturn($collection);

        $source = new CategoryList($factory);
        $options = $source->toOptionArray();

        $this->assertCount(3, $options);
        $this->assertSame('', $options[0]['value']);
        $this->assertSame([3, 7], [$options[1]['value'], $options[2]['value']]);
        $this->assertSame(['Service', 'Support'], [$options[1]['label'], $options[2]['label']]);
        $this->assertSame($options, $source->toOptionArray());
    }
}
