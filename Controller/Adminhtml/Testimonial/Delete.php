<?php
declare(strict_types=1);

namespace Panth\Testimonials\Controller\Adminhtml\Testimonial;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Panth\Testimonials\Model\TestimonialFactory;
use Panth\Testimonials\Model\ResourceModel\Testimonial as TestimonialResource;

class Delete extends Action
{
    const ADMIN_RESOURCE = 'Panth_Testimonials::testimonials';

    public function __construct(
        Context $context,
        private readonly TestimonialFactory $testimonialFactory,
        private readonly TestimonialResource $testimonialResource
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($id) {
            try {
                $testimonial = $this->testimonialFactory->create();
                $this->testimonialResource->load($testimonial, $id);
                $this->testimonialResource->delete($testimonial);
                $this->messageManager->addSuccessMessage(__('Testimonial deleted.'));
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }
        return $resultRedirect->setPath('*/*/');
    }
}
