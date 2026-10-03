<?php
declare(strict_types=1);

namespace Panth\Testimonials\Controller\Adminhtml\Testimonial;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Panth\Testimonials\Model\TestimonialFactory;
use Panth\Testimonials\Model\ResourceModel\Testimonial as TestimonialResource;

class Save extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Panth_Testimonials::manage_testimonials';

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
            if (isset($data['rating'])) {
                $testimonial->setData('rating', max(1, min(5, (int) $data['rating'])));
            }
            if (!$id) {
                $testimonial->unsetData('testimonial_id');
            }

            $urlKey = $testimonial->getData('url_key');
            if (empty($urlKey)) {
                $urlKey = $testimonial->getData('customer_name') . '-' . $testimonial->getData('title');
            }
            $urlKey = preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($urlKey)));
            $urlKey = trim($urlKey, '-');
            $testimonial->setData('url_key', $this->getUniqueUrlKey($urlKey, $id));

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

    private function getUniqueUrlKey(string $urlKey, int $id): string
    {
        $urlKey = mb_substr($urlKey, 0, 200);
        if ($urlKey === '') {
            $urlKey = 'testimonial';
        }
        $connection = $this->testimonialResource->getConnection();
        $table = $this->testimonialResource->getMainTable();
        $candidate = $urlKey;
        for ($i = 2; $i < 1000; $i++) {
            $select = $connection->select()
                ->from($table, 'testimonial_id')
                ->where('url_key = ?', $candidate)
                ->where('testimonial_id != ?', $id)
                ->limit(1);
            if (!$connection->fetchOne($select)) {
                return $candidate;
            }
            $candidate = $urlKey . '-' . $i;
        }

        return $urlKey . '-' . substr(hash('sha256', uniqid('', true)), 0, 8);
    }
}
