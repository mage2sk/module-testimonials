<?php
declare(strict_types=1);

namespace Panth\Testimonials\Model\ResourceModel\Category\Grid;

use Magento\Framework\Api\Search\AggregationInterface;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Psr\Log\LoggerInterface;

class Collection extends SearchResult
{
    protected $_idFieldName = 'category_id';
    protected $aggregations;

    public function __construct(
        EntityFactoryInterface $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        string $mainTable = 'panth_testimonial_category',
        string $resourceModel = \Panth\Testimonials\Model\ResourceModel\Category::class,
        ?string $identifierName = null,
        ?string $connectionName = null
    ) {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel, $identifierName, $connectionName);
    }

    protected function _initSelect(): static
    {
        parent::_initSelect();
        $this->addFilterToMap('category_id', 'main_table.category_id');
        return $this;
    }

    protected function _afterLoad(): static
    {
        parent::_afterLoad();
        foreach ($this->_items as $item) {
            if ($item->getData('category_id')) {
                $item->setId($item->getData('category_id'));
            }
        }
        return $this;
    }

    public function getAggregations(): AggregationInterface { return $this->aggregations; }
    public function setAggregations($aggregations): static { $this->aggregations = $aggregations; return $this; }
    public function getSearchCriteria(): ?SearchCriteriaInterface { return null; }
    public function setSearchCriteria(SearchCriteriaInterface $searchCriteria): static { return $this; }
    public function getTotalCount(): int { return $this->getSize(); }
    public function setTotalCount($totalCount): static { return $this; }
    public function setItems(?array $items = null): static { return $this; }
}
