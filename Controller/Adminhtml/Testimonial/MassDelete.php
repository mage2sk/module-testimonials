<?php
declare(strict_types=1);

namespace Panth\Testimonials\Controller\Adminhtml\Testimonial;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Panth\Testimonials\Model\ResourceModel\Testimonial\CollectionFactory;
use Panth\Testimonials\Model\ResourceModel\Testimonial as TestimonialResource;

class MassDelete extends Action
{
    const ADMIN_RESOURCE = 'Panth_Testimonials::testimonials';

    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly TestimonialResource $testimonialResource
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $count = 0;
        foreach ($collection as $item) {
            $this->testimonialResource->delete($item);
            $count++;
        }
        $this->messageManager->addSuccessMessage(__('Deleted %1 testimonial(s).', $count));
        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }
}
