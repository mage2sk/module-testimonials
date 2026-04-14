<?php
/**
 * Copyright (c) Panth Infotech. All rights reserved.
 */
declare(strict_types=1);

namespace Panth\Testimonials\Controller\View;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\View\Result\PageFactory;
use Panth\Testimonials\Helper\Data as DataHelper;
use Panth\Testimonials\Model\ResourceModel\Testimonial\CollectionFactory;
use Panth\Testimonials\Model\Testimonial;

class Index extends Action
{
    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly ForwardFactory $forwardFactory,
        private readonly CollectionFactory $collectionFactory,
        private readonly DataHelper $helper
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $urlKey = $this->getRequest()->getParam('url_key');
        if (!$urlKey) {
            return $this->forwardFactory->create()->forward('noroute');
        }

        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('url_key', $urlKey)
                   ->addFieldToFilter('status', Testimonial::STATUS_APPROVED)
                   ->setPageSize(1);

        $testimonial = $collection->getFirstItem();
        if (!$testimonial->getId()) {
            return $this->forwardFactory->create()->forward('noroute');
        }

        $resultPage = $this->resultPageFactory->create();

        // Set meta title from testimonial or fall back to testimonial title
        $metaTitle = $testimonial->getMetaTitle() ?: $testimonial->getTitle();
        $resultPage->getConfig()->getTitle()->set($metaTitle);

        // Set meta description
        $metaDesc = $testimonial->getMetaDescription();
        if ($metaDesc) {
            $resultPage->getConfig()->setDescription($metaDesc);
        }

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
            $breadcrumbs->addCrumb('testimonial', [
                'label' => $testimonial->getTitle(),
                'title' => $testimonial->getTitle()
            ]);
        }

        return $resultPage;
    }
}
