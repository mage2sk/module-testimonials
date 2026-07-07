<?php
declare(strict_types=1);

namespace Panth\Testimonials\Controller\Index;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\Page;
use Panth\Testimonials\Helper\Data as DataHelper;

class Index implements HttpGetActionInterface
{
    public function __construct(
        private readonly ResultFactory $resultFactory,
        private readonly DataHelper $helper
    ) {}

    public function execute(): Page
    {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        $pageTitle = $this->helper->getPageTitle();
        $resultPage->getConfig()->getTitle()->set($pageTitle);

        $metaDescription = $this->helper->getMetaDescription();
        $pageConfig = $resultPage->getConfig();
        $pageConfig->setDescription($metaDescription);

        $breadcrumbs = $resultPage->getLayout()->getBlock('breadcrumbs');
        if ($breadcrumbs) {
            $breadcrumbs->addCrumb('home', [
                'label' => __('Home'),
                'title' => __('Home'),
                'link' => '/'
            ]);
            $breadcrumbs->addCrumb('testimonials', [
                'label' => $this->helper->getPageTitle(),
                'title' => $this->helper->getPageTitle()
            ]);
        }

        return $resultPage;
    }
}
