<?php
declare(strict_types=1);

namespace Panth\Testimonials\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\StoreManagerInterface;
use Panth\Testimonials\Model\ResourceModel\Testimonial\CollectionFactory;
use Panth\Testimonials\Model\Testimonial;

class Schema extends Template
{
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

        $organizationId = $baseUrl . '#organization';
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
                'itemReviewed' => ['@id' => $organizationId],
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
            'url' => $baseUrl . 'testimonials',
            'numberOfItems' => $count,
            'itemListElement' => $elements,
        ];

        return $this->json->serialize($schema);
    }
}
