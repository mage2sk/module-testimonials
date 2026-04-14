<?php
declare(strict_types=1);

namespace Panth\Testimonials\Block\Adminhtml\Testimonial\Edit;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteButton implements ButtonProviderInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly UrlInterface $urlBuilder
    ) {}

    public function getButtonData(): array
    {
        $id = (int)$this->request->getParam('id');
        if (!$id) return [];

        return [
            'label' => __('Delete'),
            'class' => 'delete',
            'on_click' => 'deleteConfirm("' . __('Are you sure?') . '", "' . $this->urlBuilder->getUrl('*/*/delete', ['id' => $id]) . '")',
            'sort_order' => 20,
        ];
    }
}
