<?php
declare(strict_types=1);

namespace Panth\Testimonials\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class CategoryActions extends Column
{
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $name = $this->getData('name');
                $item[$name]['edit'] = [
                    'href' => $this->urlBuilder->getUrl('panth_testimonials/category/edit', ['id' => $item['category_id']]),
                    'label' => __('Edit')
                ];
                $item[$name]['delete'] = [
                    'href' => $this->urlBuilder->getUrl('panth_testimonials/category/delete', ['id' => $item['category_id']]),
                    'label' => __('Delete'),
                    'confirm' => ['title' => __('Delete'), 'message' => __('Are you sure?')]
                ];
            }
        }
        return $dataSource;
    }
}
