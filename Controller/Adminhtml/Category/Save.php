<?php
declare(strict_types=1);

namespace Panth\Testimonials\Controller\Adminhtml\Category;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Panth\Testimonials\Model\CategoryFactory;
use Panth\Testimonials\Model\ResourceModel\Category as CategoryResource;

class Save extends Action
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
        $data = $this->getRequest()->getPostValue();
        $resultRedirect = $this->resultRedirectFactory->create();

        if (!$data) {
            return $resultRedirect->setPath('*/*/');
        }

        try {
            $id = (int)($data['category_id'] ?? 0);
            $category = $this->categoryFactory->create();
            if ($id) {
                $this->categoryResource->load($category, $id);
            }

            $category->setData($data);
            if (!$id) {
                $category->unsetData('category_id');
            }

            // Always sanitize URL key — no spaces, lowercase, hyphens only
            $urlKey = $category->getData('url_key');
            if (empty($urlKey)) {
                $urlKey = $category->getData('name');
            }
            $urlKey = preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($urlKey)));
            $urlKey = trim($urlKey, '-');
            $category->setData('url_key', $urlKey);

            $this->categoryResource->save($category);
            $this->messageManager->addSuccessMessage(__('Category saved.'));

            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', ['id' => $category->getId()]);
            }
            return $resultRedirect->setPath('*/*/');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $resultRedirect->setPath('*/*/edit', ['id' => $id ?? 0]);
        }
    }
}
