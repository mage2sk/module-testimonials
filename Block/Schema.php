<?php
declare(strict_types=1);

namespace Panth\Testimonials\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Panth\Testimonials\Model\ResourceModel\Testimonial\CollectionFactory;
use Panth\Testimonials\Model\Testimonial;

class Schema extends Template
{
    private const XML_PATH_ITEM_REVIEWED_TYPE = 'panth_testimonials/schema/item_reviewed_type';
    private const XML_PATH_ITEM_REVIEWED_NAME = 'panth_testimonials/schema/item_reviewed_name';
    private const XML_PATH_ITEM_REVIEWED_ID   = 'panth_testimonials/schema/item_reviewed_id';
    private const XML_PATH_STORE_INFO_NAME    = 'general/store_information/name';
    private const XML_PATH_ROUTE              = 'panth_testimonials/general/route';
    private const DEFAULT_ROUTE               = 'testimonials';
    private const DEFAULT_ITEM_REVIEWED_TYPE  = 'Organization';

    public function __construct(
        Context $context,
        private readonly CollectionFactory $collectionFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly Json $json,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getSchemaJson(): string
    {
        try {
            $store = $this->storeManager->getStore();
            $baseUrl = rtrim((string) $store->getBaseUrl(), '/') . '/';
            $storeId = (int) $store->getId();
        } catch (\Throwable) {
            return '';
        }

        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('status', Testimonial::STATUS_APPROVED)
                   ->addFieldToFilter('store_id', ['in' => [0, $storeId]])
                   ->setOrder('sort_order', 'ASC')
                   ->setOrder('created_at', 'DESC')
                   ->setPageSize(50);

        $itemReviewed = $this->buildItemReviewed($store, $baseUrl);
        $elements = [];
        $count = 0;
        $position = 1;

        foreach ($collection as $testimonial) {
            $rating = (float) $testimonial->getRating();
            $review = [
                '@type' => 'Review',
                'author' => [
                    '@type' => 'Person',
                    'name' => $testimonial->getCustomerName(),
                ],
                'reviewBody' => $testimonial->getContent(),
                'name' => $testimonial->getTitle(),
                'itemReviewed' => $itemReviewed,
                'reviewRating' => [
                    '@type' => 'Rating',
                    'ratingValue' => $rating,
                    'bestRating' => 5,
                    'worstRating' => 1,
                ],
                'datePublished' => date('Y-m-d', strtotime((string) $testimonial->getCreatedAt())),
            ];

            if ($testimonial->getCustomerCompany()) {
                $review['author']['affiliation'] = [
                    '@type' => 'Thing',
                    'name' => $testimonial->getCustomerCompany(),
                ];
            }

            if ($testimonial->getCustomerTitle()) {
                $review['author']['jobTitle'] = $testimonial->getCustomerTitle();
            }

            $elements[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'item' => $review,
            ];
            $count++;
        }

        if ($count === 0) {
            return '';
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            '@id' => $baseUrl . '#testimonials',
            'name' => 'Customer Testimonials',
            'url' => $baseUrl . $this->resolveRoute($storeId),
            'numberOfItems' => $count,
            'itemListElement' => $elements,
        ];

        return $this->json->serialize($schema);
    }

    private function resolveRoute(int $storeId): string
    {
        $route = trim((string) $this->_scopeConfig->getValue(
            self::XML_PATH_ROUTE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ), '/ ');

        return $route !== '' ? $route : self::DEFAULT_ROUTE;
    }

    private function buildItemReviewed(StoreInterface $store, string $baseUrl): array
    {
        $storeId = (int) $store->getId();

        $type = trim((string) $this->_scopeConfig->getValue(
            self::XML_PATH_ITEM_REVIEWED_TYPE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));
        if ($type === '') {
            $type = self::DEFAULT_ITEM_REVIEWED_TYPE;
        }

        $name = trim((string) $this->_scopeConfig->getValue(
            self::XML_PATH_ITEM_REVIEWED_NAME,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));
        if ($name === '') {
            $name = $this->resolveStoreName($store, $storeId);
        }

        $itemReviewed = [
            '@type' => $type,
            'name' => $name,
        ];

        $id = trim((string) $this->_scopeConfig->getValue(
            self::XML_PATH_ITEM_REVIEWED_ID,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));
        if ($id !== '') {
            $itemReviewed['@id'] = preg_match('#^https?://#i', $id) === 1
                ? $id
                : $baseUrl . ltrim($id, '/');
        }

        return $itemReviewed;
    }

    private function resolveStoreName(StoreInterface $store, int $storeId): string
    {
        $storeName = trim((string) $this->_scopeConfig->getValue(
            self::XML_PATH_STORE_INFO_NAME,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));
        if ($storeName !== '') {
            return $storeName;
        }

        $frontendName = method_exists($store, 'getFrontendName') ? trim((string) $store->getFrontendName()) : '';
        if ($frontendName !== '') {
            return $frontendName;
        }

        try {
            $websiteName = trim((string) $this->storeManager->getWebsite($store->getWebsiteId())->getName());
            if ($websiteName !== '') {
                return $websiteName;
            }
        } catch (\Throwable) {
        }

        return (string) $store->getName();
    }
}
