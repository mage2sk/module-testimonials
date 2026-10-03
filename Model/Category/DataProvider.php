<?php
declare(strict_types=1);

namespace Panth\Testimonials\Model\Category;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Panth\Testimonials\Model\ResourceModel\Category\CollectionFactory;

class DataProvider extends AbstractDataProvider
{
    private array $loadedData = [];

    public function __construct(
        $name, $primaryFieldName, $requestFieldName,
        CollectionFactory $collectionFactory,
        private readonly DataPersistorInterface $dataPersistor,
        array $meta = [], array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData(): array
    {
        if (!empty($this->loadedData)) return $this->loadedData;
        foreach ($this->collection->getItems() as $item) {
            $this->loadedData[$item->getId()] = $item->getData();
        }
        $data = $this->dataPersistor->get('panth_testimonial_category');
        if (!empty($data)) {
            $item = $this->collection->getNewEmptyItem();
            $item->setData($data);
            $this->loadedData[$item->getId()] = $item->getData();
            $this->dataPersistor->clear('panth_testimonial_category');
        }
        return $this->loadedData;
    }
}
