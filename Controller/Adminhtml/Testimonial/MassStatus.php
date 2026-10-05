<?php
declare(strict_types=1);

namespace Panth\Testimonials\Controller\Adminhtml\Testimonial;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Ui\Component\MassAction\Filter;
use Panth\Testimonials\Model\ResourceModel\Testimonial as TestimonialResource;
use Panth\Testimonials\Model\ResourceModel\Testimonial\CollectionFactory;
use Panth\Testimonials\Model\Testimonial;

class MassStatus extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Panth_Testimonials::manage_testimonials';

    private const ALLOWED = [
        Testimonial::STATUS_PENDING,
        Testimonial::STATUS_APPROVED,
        Testimonial::STATUS_REJECTED,
    ];

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
        $resultRedirect = $this->resultRedirectFactory->create()->setPath('*/*/');
        $status = $this->getRequest()->getParam('status');
        if ($status === null || !is_numeric($status) || !in_array((int) $status, self::ALLOWED, true)) {
            $this->messageManager->addErrorMessage(__('Please choose a valid status.'));
            return $resultRedirect;
        }
        $status = (int) $status;

        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $count = 0;
            foreach ($collection as $item) {
                if ((int) $item->getData('status') === $status) {
                    continue;
                }
                $item->setData('status', $status);
                $this->testimonialResource->save($item);
                $count++;
            }
            $this->messageManager->addSuccessMessage(__('Updated the status of %1 testimonial(s).', $count));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $resultRedirect;
    }
}
