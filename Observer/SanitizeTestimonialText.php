<?php
declare(strict_types=1);

namespace Panth\Testimonials\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Panth\Testimonials\Model\PlainTextSanitizer;
use Panth\Testimonials\Model\Testimonial;

class SanitizeTestimonialText implements ObserverInterface
{
    private const TEXT_FIELDS = [
        'customer_email',
        'customer_title',
        'customer_company',
        'title',
        'content',
        'short_content',
        'meta_title',
        'meta_description',
    ];

    private const ATTRIBUTE_FIELDS = [
        'customer_name',
    ];

    private const REQUIRED_FIELDS = [
        'customer_name' => 'Name',
        'title' => 'Title',
        'content' => 'Content',
    ];

    public function __construct(
        private readonly PlainTextSanitizer $sanitizer
    ) {}

    public function execute(Observer $observer): void
    {
        $testimonial = $observer->getEvent()->getData('object');
        if (!$testimonial instanceof Testimonial) {
            return;
        }

        foreach (self::TEXT_FIELDS as $field) {
            $value = $testimonial->getData($field);
            if (is_scalar($value)) {
                $testimonial->setData($field, $this->sanitizer->sanitize((string) $value));
            }
        }

        foreach (self::ATTRIBUTE_FIELDS as $field) {
            $value = $testimonial->getData($field);
            if (is_scalar($value)) {
                $testimonial->setData($field, $this->sanitizer->sanitizeAttributeSafe((string) $value));
            }
        }

        foreach (self::REQUIRED_FIELDS as $field => $label) {
            if ($testimonial->hasData($field) && trim((string) $testimonial->getData($field)) === '') {
                throw new LocalizedException(__('%1 is required and cannot contain only HTML markup.', __($label)));
            }
        }
    }
}
