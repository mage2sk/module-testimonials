<?php
/**
 * Copyright (c) Panth Infotech. All rights reserved.
 */
declare(strict_types=1);

namespace Panth\Testimonials\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Panth\Testimonials\Helper\Data as DataHelper;

class Submit extends Template
{
    public function __construct(
        Context $context,
        private readonly DataHelper $helper,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Check if testimonial submission is enabled
     */
    public function isEnabled(): bool
    {
        return $this->helper->isSubmitEnabled();
    }

    /**
     * Get the POST action URL for the submit form
     */
    public function getSubmitPostUrl(): string
    {
        return $this->getUrl('testimonials/submit/save');
    }
}
