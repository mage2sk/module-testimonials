<?php
declare(strict_types=1);

namespace Panth\Testimonials\Controller\Adminhtml\Category;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Panth\Testimonials\Model\CategoryFactory;
use Panth\Testimonials\Model\ResourceModel\Category as CategoryResource;

class Delete extends Action
{
    const ADMIN_RESOURCE = 'Panth_Testimonials::manage_categories';

    public function __construct(
        Context $context,
        private readonly CategoryFactory $categoryFactory,
        private readonly CategoryResource $categoryResource
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($id) {
            try {
                $category = $this->categoryFactory->create();
                $this->categoryResource->load($category, $id);
                $this->categoryResource->delete($category);
                $this->messageManager->addSuccessMessage(__('Category deleted.'));
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }
        return $resultRedirect->setPath('*/*/');
    }
}
