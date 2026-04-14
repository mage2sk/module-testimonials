<?php
declare(strict_types=1);

namespace Panth\Testimonials\Controller\Adminhtml\Testimonial;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Panth\Testimonials\Model\TestimonialFactory;
use Panth\Testimonials\Model\ResourceModel\Testimonial as TestimonialResource;

class Save extends Action
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
        $data = $this->getRequest()->getPostValue();
        $resultRedirect = $this->resultRedirectFactory->create();

        if (!$data) {
            return $resultRedirect->setPath('*/*/');
        }

        try {
            $id = (int)($data['testimonial_id'] ?? 0);
            $testimonial = $this->testimonialFactory->create();
            if ($id) {
                $this->testimonialResource->load($testimonial, $id);
            }

            $testimonial->setData($data);
            if (!$id) {
                $testimonial->unsetData('testimonial_id');
            }

            // Always sanitize URL key — no spaces, lowercase, hyphens only
            $urlKey = $testimonial->getData('url_key');
            if (empty($urlKey)) {
                $urlKey = $testimonial->getData('customer_name') . '-' . $testimonial->getData('title');
            }
            $urlKey = preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($urlKey)));
            $urlKey = trim($urlKey, '-');
            $testimonial->setData('url_key', $urlKey);

            $this->testimonialResource->save($testimonial);
            $this->messageManager->addSuccessMessage(__('Testimonial saved successfully.'));

            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', ['id' => $testimonial->getId()]);
            }
            return $resultRedirect->setPath('*/*/');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $resultRedirect->setPath('*/*/edit', ['id' => $id]);
        }
    }
}
