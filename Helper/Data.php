<?php
/**
 * Copyright (c) Panth Infotech. All rights reserved.
 */
declare(strict_types=1);

namespace Panth\Testimonials\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    public const XML_PATH_ENABLED = 'panth_testimonials/general/enabled';
    public const XML_PATH_PAGE_TITLE = 'panth_testimonials/general/page_title';
    public const XML_PATH_META_DESCRIPTION = 'panth_testimonials/general/meta_description';
    public const XML_PATH_ROUTE = 'panth_testimonials/general/route';
    public const XML_PATH_SUBMIT_ENABLED = 'panth_testimonials/submit/enabled';
    public const XML_PATH_REQUIRE_APPROVAL = 'panth_testimonials/submit/require_approval';
    public const XML_PATH_ITEMS_PER_PAGE = 'panth_testimonials/display/items_per_page';

    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getPageTitle(?int $storeId = null): string
    {
        return (string) ($this->scopeConfig->getValue(
            self::XML_PATH_PAGE_TITLE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 'Customer Testimonials');
    }

    public const DEFAULT_META_DESCRIPTION = 'Read verified customer testimonials and reviews. Real feedback from real customers about their experience.';

    public function getMetaDescription(?int $storeId = null): string
    {
        $configured = (string) ($this->scopeConfig->getValue(
            self::XML_PATH_META_DESCRIPTION,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: '');

        if ($configured !== '') {
            return $configured;
        }

        return self::DEFAULT_META_DESCRIPTION;
    }

    /**
     * Build a default description for a testimonials category page.
     */
    public function getCategoryMetaDescription(string $categoryName, ?string $categoryDescription = null, ?int $storeId = null): string
    {
        $categoryDescription = $categoryDescription !== null ? trim(strip_tags($categoryDescription)) : '';
        if ($categoryDescription !== '') {
            return $categoryDescription;
        }

        return sprintf(
            'Customer testimonials in the %s category. Real reviews from real customers sharing their experience.',
            $categoryName
        );
    }

    public function getBaseUrl(?int $storeId = null): string
    {
        return (string) ($this->scopeConfig->getValue(
            self::XML_PATH_ROUTE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 'testimonials');
    }

    public function isSubmitEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_SUBMIT_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function requireApproval(?int $storeId = null): bool
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_REQUIRE_APPROVAL,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        // Default to true if not set
        return $value === null ? true : (bool) $value;
    }

    public function getItemsPerPage(?int $storeId = null): int
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_ITEMS_PER_PAGE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $value ? (int) $value : 12;
    }
}
