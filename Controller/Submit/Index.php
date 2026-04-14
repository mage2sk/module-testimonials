<?php
/**
 * Copyright (c) Panth Infotech. All rights reserved.
 */
declare(strict_types=1);

namespace Panth\Testimonials\Controller\Submit;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\Page;
use Panth\Testimonials\Helper\Data as DataHelper;

class Index implements HttpGetActionInterface
{
    public function __construct(
        private readonly ResultFactory $resultFactory,
        private readonly ForwardFactory $forwardFactory,
        private readonly DataHelper $helper
    ) {}

    public function execute(): Page|\Magento\Framework\Controller\Result\Forward
    {
        if (!$this->helper->isSubmitEnabled()) {
            return $this->forwardFactory->create()->forward('noroute');
        }

        /** @var Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        $resultPage->getConfig()->getTitle()->set(__('Submit a Testimonial'));

        // Breadcrumbs
        $breadcrumbs = $resultPage->getLayout()->getBlock('breadcrumbs');
        if ($breadcrumbs) {
            $breadcrumbs->addCrumb('home', [
                'label' => __('Home'),
                'title' => __('Home'),
                'link' => '/'
            ]);
            $breadcrumbs->addCrumb('testimonials', [
                'label' => $this->helper->getPageTitle(),
                'title' => $this->helper->getPageTitle(),
                'link' => '/' . $this->helper->getBaseUrl()
            ]);
            $breadcrumbs->addCrumb('submit', [
                'label' => __('Submit a Testimonial'),
                'title' => __('Submit a Testimonial')
            ]);
        }

        return $resultPage;
    }
}
