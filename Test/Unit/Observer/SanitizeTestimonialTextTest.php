<?php
declare(strict_types=1);

namespace Panth\Testimonials\Test\Unit\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Panth\Testimonials\Model\PlainTextSanitizer;
use Panth\Testimonials\Model\Testimonial;
use Panth\Testimonials\Observer\SanitizeTestimonialText;
use PHPUnit\Framework\TestCase;

class SanitizeTestimonialTextTest extends TestCase
{
    private SanitizeTestimonialText $observer;

    protected function setUp(): void
    {
        $this->observer = new SanitizeTestimonialText(new PlainTextSanitizer());
    }

    private function testimonial(array $data): Testimonial
    {
        $model = (new \ReflectionClass(Testimonial::class))->newInstanceWithoutConstructor();
        $model->setData($data);
        return $model;
    }

    private function dispatch(object $object): void
    {
        $this->observer->execute(new Observer(['event' => new Event(['object' => $object])]));
    }

    public function testStripsMarkupFromTextAndAttributeFields(): void
    {
        $model = $this->testimonial([
            'customer_name' => '<b>Jane "JJ"</b>',
            'title' => '<h1>Great</h1>',
            'content' => '<script>x</script>Loved it',
            'meta_description' => '<p>Meta</p>',
            'rating' => 5,
        ]);

        $this->dispatch($model);

        $this->assertSame("Jane 'JJ'", $model->getData('customer_name'));
        $this->assertSame('Great', $model->getData('title'));
        $this->assertSame('xLoved it', $model->getData('content'));
        $this->assertSame('Meta', $model->getData('meta_description'));
        $this->assertSame(5, $model->getData('rating'));
    }

    public function testRequiredFieldContainingOnlyMarkupIsRejected(): void
    {
        $model = $this->testimonial(['customer_name' => 'Jane', 'title' => '<p></p>', 'content' => 'ok']);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Title is required');
        $this->dispatch($model);
    }

    public function testAbsentRequiredFieldsAreNotValidated(): void
    {
        $model = $this->testimonial(['status' => 1]);

        $this->dispatch($model);

        $this->assertFalse($model->hasData('title'));
        $this->assertSame(1, $model->getData('status'));
    }

    public function testNonScalarValuesAreLeftAlone(): void
    {
        $model = $this->testimonial([
            'customer_name' => 'A',
            'title' => 'T',
            'content' => 'C',
            'short_content' => ['x'],
        ]);

        $this->dispatch($model);

        $this->assertSame(['x'], $model->getData('short_content'));
    }

    public function testOtherObjectsAreIgnored(): void
    {
        $object = new DataObject(['title' => '<b>x</b>']);

        $this->dispatch($object);

        $this->assertSame('<b>x</b>', $object->getData('title'));
    }
}
