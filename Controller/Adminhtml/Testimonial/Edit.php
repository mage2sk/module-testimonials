<?php
declare(strict_types=1);

namespace Panth\Testimonials\Controller\Adminhtml\Testimonial;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Edit extends Action
{
    const ADMIN_RESOURCE = 'Panth_Testimonials::testimonials';

    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Panth_Testimonials::testimonials');
        $resultPage->getConfig()->getTitle()->prepend($id ? __('Edit Testimonial') : __('New Testimonial'));
        return $resultPage;
    }
}
