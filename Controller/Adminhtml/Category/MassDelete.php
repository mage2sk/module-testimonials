<?php
declare(strict_types=1);

namespace Panth\Testimonials\Controller\Adminhtml\Category;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Panth\Testimonials\Model\ResourceModel\Category\CollectionFactory;
use Panth\Testimonials\Model\ResourceModel\Category as CategoryResource;

class MassDelete extends Action
{
    const ADMIN_RESOURCE = 'Panth_Testimonials::manage_categories';

    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly CategoryResource $categoryResource
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $count = 0;
        foreach ($collection as $item) {
            $this->categoryResource->delete($item);
            $count++;
        }
        $this->messageManager->addSuccessMessage(__('Deleted %1 category(ies).', $count));
        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }
}
