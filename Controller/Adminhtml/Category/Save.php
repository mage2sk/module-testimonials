<?php
declare(strict_types=1);

namespace Panth\Testimonials\Controller\Adminhtml\Category;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Panth\Testimonials\Model\CategoryFactory;
use Panth\Testimonials\Model\ResourceModel\Category as CategoryResource;

class Save extends Action implements HttpPostActionInterface
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

            $urlKey = $category->getData('url_key');
            if (empty($urlKey)) {
                $urlKey = $category->getData('name');
            }
            $urlKey = preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($urlKey)));
            $urlKey = trim($urlKey, '-');
            $category->setData('url_key', $this->getUniqueUrlKey($urlKey, $id));

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

    private function getUniqueUrlKey(string $urlKey, int $id): string
    {
        $urlKey = mb_substr($urlKey, 0, 200);
        if ($urlKey === '') {
            $urlKey = 'category';
        }
        $connection = $this->categoryResource->getConnection();
        $table = $this->categoryResource->getMainTable();
        $candidate = $urlKey;
        for ($i = 2; $i < 1000; $i++) {
            $select = $connection->select()
                ->from($table, 'category_id')
                ->where('url_key = ?', $candidate)
                ->where('category_id != ?', $id)
                ->limit(1);
            if (!$connection->fetchOne($select)) {
                return $candidate;
            }
            $candidate = $urlKey . '-' . $i;
        }

        return $urlKey . '-' . substr(hash('sha256', uniqid('', true)), 0, 8);
    }
}
