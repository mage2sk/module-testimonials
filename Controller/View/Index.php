<?php
declare(strict_types=1);

namespace Panth\Testimonials\Controller\View;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;
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
        private readonly DataHelper $helper,
        private readonly StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $urlKey = $this->getRequest()->getParam('url_key');
        if (!$this->helper->isEnabled() || !$urlKey || !is_string($urlKey)) {
            return $this->forwardFactory->create()->setModule('cms')->setController('noroute')->forward('index');
        }

        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('url_key', $urlKey)
                   ->addFieldToFilter('status', Testimonial::STATUS_APPROVED)
                   ->addStoreFilter((int) $this->storeManager->getStore()->getId())
                   ->setPageSize(1);

        $testimonial = $collection->getFirstItem();
        if (!$testimonial->getId()) {
            return $this->forwardFactory->create()->setModule('cms')->setController('noroute')->forward('index');
        }

        $resultPage = $this->resultPageFactory->create();

        $metaTitle = $testimonial->getMetaTitle() ?: $testimonial->getTitle();
        $resultPage->getConfig()->getTitle()->set($metaTitle);

        $metaDesc = $testimonial->getMetaDescription();
        if ($metaDesc) {
            $resultPage->getConfig()->setDescription($metaDesc);
        }

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
