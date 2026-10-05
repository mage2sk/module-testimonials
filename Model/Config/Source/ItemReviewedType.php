<?php
declare(strict_types=1);

namespace Panth\Testimonials\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ItemReviewedType implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'Organization', 'label' => __('Organization')],
            ['value' => 'LocalBusiness', 'label' => __('LocalBusiness')],
            ['value' => 'ProfessionalService', 'label' => __('ProfessionalService')],
            ['value' => 'Product', 'label' => __('Product')],
            ['value' => 'Service', 'label' => __('Service')],
        ];
    }
}
