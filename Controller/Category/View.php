<?php
declare(strict_types=1);

namespace Panth\Testimonials\Controller\Category;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\View\Result\PageFactory;
use Panth\Testimonials\Helper\Data as DataHelper;
use Panth\Testimonials\Model\ResourceModel\Category\CollectionFactory;

class View extends Action
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
                   ->addFieldToFilter('is_active', 1)
                   ->setPageSize(1);

        $category = $collection->getFirstItem();
        if (!$category->getId()) {
            return $this->forwardFactory->create()->forward('noroute');
        }

        $resultPage = $this->resultPageFactory->create();
        $pageTitle = $category->getName() . ' - ' . $this->helper->getPageTitle();
        $pageConfig = $resultPage->getConfig();
        $pageConfig->getTitle()->set($pageTitle);

        $metaDescription = $this->helper->getCategoryMetaDescription(
            (string) $category->getName(),
            $category->getDescription()
        );
        $pageConfig->setDescription($metaDescription);
        // OpenGraph + Twitter Card tags are emitted centrally by
        // Panth_AdvancedSEO via its canonical.phtml ViewModels. Emitting
        // them here as well produced duplicate og:title / og:description /
        // twitter:* tags in the <head>. AdvancedSEO's OpenGraphResolver
        // falls back to the controller-set page title/description on
        // routes without a catalog/CMS entity in the registry, so the
        // single emission still renders the correct values.

        $breadcrumbs = $resultPage->getLayout()->getBlock('breadcrumbs');
        if ($breadcrumbs) {
            $breadcrumbs->addCrumb('home', ['label' => __('Home'), 'title' => __('Home'), 'link' => '/']);
            $breadcrumbs->addCrumb('testimonials', ['label' => $this->helper->getPageTitle(), 'title' => $this->helper->getPageTitle(), 'link' => '/' . $this->helper->getBaseUrl()]);
            $breadcrumbs->addCrumb('category', ['label' => $category->getName(), 'title' => $category->getName()]);
        }

        return $resultPage;
    }
}
