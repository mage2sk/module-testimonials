<?php
/**
 * Copyright (c) Panth Infotech. All rights reserved.
 */
declare(strict_types=1);

namespace Panth\Testimonials\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Panth\Testimonials\Model\Testimonial;

class Status implements OptionSourceInterface
{
    /**
     * @return array<int, array{value: int, label: string}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => Testimonial::STATUS_PENDING, 'label' => __('Pending')],
            ['value' => Testimonial::STATUS_APPROVED, 'label' => __('Approved')],
            ['value' => Testimonial::STATUS_REJECTED, 'label' => __('Rejected')],
        ];
    }

    /**
     * @return array<int, string>
     */
    public function toArray(): array
    {
        return [
            Testimonial::STATUS_PENDING => __('Pending'),
            Testimonial::STATUS_APPROVED => __('Approved'),
            Testimonial::STATUS_REJECTED => __('Rejected'),
        ];
    }
}
