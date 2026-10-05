<?php
declare(strict_types=1);

namespace Panth\Testimonials\Test\Unit\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Panth\Testimonials\Helper\Data;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DataTest extends TestCase
{
    private function helper(array $values = [], array $flags = []): Data
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(static fn(string $p) => $values[$p] ?? null);
        $scopeConfig->method('isSetFlag')->willReturnCallback(static fn(string $p) => (bool) ($flags[$p] ?? false));
        $context = $this->createStub(Context::class);
        $context->method('getScopeConfig')->willReturn($scopeConfig);

        return new Data($context);
    }

    public function testDefaultsWhenNothingConfigured(): void
    {
        $helper = $this->helper();

        $this->assertFalse($helper->isEnabled());
        $this->assertFalse($helper->isSubmitEnabled());
        $this->assertFalse($helper->isAssignSubmissionStore());
        $this->assertSame('Customer Testimonials', $helper->getPageTitle());
        $this->assertSame(Data::DEFAULT_META_DESCRIPTION, $helper->getMetaDescription());
        $this->assertSame('testimonials', $helper->getBaseUrl());
        $this->assertSame(12, $helper->getItemsPerPage());
        $this->assertTrue($helper->requireApproval(), 'approval is required when the setting is missing');
    }

    public function testConfiguredValuesAreReturned(): void
    {
        $helper = $this->helper(
            [
                Data::XML_PATH_PAGE_TITLE => 'Reviews',
                Data::XML_PATH_META_DESCRIPTION => 'Custom meta',
                Data::XML_PATH_ROUTE => 'reviews',
                Data::XML_PATH_ITEMS_PER_PAGE => '24',
                Data::XML_PATH_REQUIRE_APPROVAL => '0',
            ],
            [
                Data::XML_PATH_ENABLED => true,
                Data::XML_PATH_SUBMIT_ENABLED => true,
                Data::XML_PATH_ASSIGN_SUBMISSION_STORE => true,
            ]
        );

        $this->assertTrue($helper->isEnabled(1));
        $this->assertTrue($helper->isSubmitEnabled(1));
        $this->assertTrue($helper->isAssignSubmissionStore(1));
        $this->assertSame('Reviews', $helper->getPageTitle(1));
        $this->assertSame('Custom meta', $helper->getMetaDescription(1));
        $this->assertSame('reviews', $helper->getBaseUrl(1));
        $this->assertSame(24, $helper->getItemsPerPage(1));
        $this->assertFalse($helper->requireApproval(1));
    }

    public function testZeroItemsPerPageFallsBackToDefault(): void
    {
        $this->assertSame(12, $this->helper([Data::XML_PATH_ITEMS_PER_PAGE => '0'])->getItemsPerPage());
    }

    #[DataProvider('categoryMetaCases')]
    public function testCategoryMetaDescription(?string $description, string $expected): void
    {
        $this->assertSame($expected, $this->helper()->getCategoryMetaDescription('Shipping', $description));
    }

    public static function categoryMetaCases(): array
    {
        $fallback = 'Customer testimonials in the Shipping category. '
            . 'Real reviews from real customers sharing their experience.';
        return [
            'null description' => [null, $fallback],
            'blank description' => ['   ', $fallback],
            'markup only' => ['<p> </p>', $fallback],
            'description stripped and trimmed' => [' <p>Fast <b>delivery</b></p> ', 'Fast delivery'],
        ];
    }
}
