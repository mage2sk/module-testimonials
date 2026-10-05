<?php
declare(strict_types=1);

namespace Panth\Testimonials\Controller\Adminhtml\Category;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;

class NewAction extends Action
{
    const ADMIN_RESOURCE = 'Panth_Testimonials::manage_categories';

    public function execute()
    {
        return $this->resultFactory->create(ResultFactory::TYPE_FORWARD)->forward('edit');
    }
}
