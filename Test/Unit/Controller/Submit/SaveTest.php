<?php
declare(strict_types=1);

namespace Panth\Testimonials\Test\Unit\Controller\Submit;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filter\FilterManager;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Panth\Testimonials\Controller\Submit\Save;
use Panth\Testimonials\Helper\Data;
use Panth\Testimonials\Model\ResourceModel\Category\Collection as CategoryCollection;
use Panth\Testimonials\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Panth\Testimonials\Model\ResourceModel\Testimonial as TestimonialResource;
use Panth\Testimonials\Model\ResourceModel\Testimonial\Collection as TestimonialCollection;
use Panth\Testimonials\Model\ResourceModel\Testimonial\CollectionFactory as TestimonialCollectionFactory;
use Panth\Testimonials\Model\Testimonial;
use Panth\Testimonials\Model\TestimonialFactory;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class SaveTest extends TestCase
{
    private array $params = [];
    private bool $ajax = true;
    private bool $enabled = true;
    private bool $submitEnabled = true;
    private bool $formKeyValid = true;
    private bool $requireApproval = true;
    private bool $assignStore = false;
    private int $categorySize = 0;
    private int $urlKeySize = 0;
    private string|false $cachedAttempts = false;
    private ?\Throwable $saveException = null;

    private ?Testimonial $saved = null;
    private array $jsonData = [];
    private ?string $redirectPath = null;
    private array $successMessages = [];
    private array $errorMessages = [];
    private array $cacheWrites = [];

    protected function setUp(): void
    {
        $this->params = [
            'customer_name' => 'Jane Doe',
            'customer_email' => 'jane@example.com',
            'title' => 'Great Service',
            'content' => 'Loved it.',
            'customer_title' => 'CTO',
            'customer_company' => 'Acme',
            'rating' => '4',
        ];
    }

    private function controller(): Save
    {
        $request = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getParam', 'isXmlHttpRequest', 'getClientIp'])
            ->getMock();
        $request->method('getParam')->willReturnCallback(
            fn(string $key, $default = null) => $this->params[$key] ?? $default
        );
        $request->method('isXmlHttpRequest')->willReturnCallback(fn() => $this->ajax);
        $request->method('getClientIp')->willReturn('10.0.0.1');

        $redirect = $this->createStub(Redirect::class);
        $redirect->method('setPath')->willReturnCallback(function (string $path) use ($redirect) {
            $this->redirectPath = $path;
            return $redirect;
        });
        $redirectFactory = $this->createStub(RedirectFactory::class);
        $redirectFactory->method('create')->willReturn($redirect);

        $json = $this->createStub(Json::class);
        $json->method('setData')->willReturnCallback(function (array $data) use ($json) {
            $this->jsonData = $data;
            return $json;
        });
        $jsonFactory = $this->createStub(JsonFactory::class);
        $jsonFactory->method('create')->willReturn($json);

        $messages = $this->createStub(ManagerInterface::class);
        $messages->method('addSuccessMessage')->willReturnCallback(function ($m) use ($messages) {
            $this->successMessages[] = (string) $m;
            return $messages;
        });
        $messages->method('addErrorMessage')->willReturnCallback(function ($m) use ($messages) {
            $this->errorMessages[] = (string) $m;
            return $messages;
        });

        $validator = $this->createStub(Validator::class);
        $validator->method('validate')->willReturnCallback(fn() => $this->formKeyValid);

        $factory = $this->createStub(TestimonialFactory::class);
        $factory->method('create')->willReturnCallback(
            fn() => (new \ReflectionClass(Testimonial::class))->newInstanceWithoutConstructor()
        );

        $resource = $this->createStub(TestimonialResource::class);
        $resource->method('save')->willReturnCallback(function (Testimonial $t) use ($resource) {
            if ($this->saveException) {
                throw $this->saveException;
            }
            $this->saved = $t;
            return $resource;
        });

        $helper = $this->createStub(Data::class);
        $helper->method('isEnabled')->willReturnCallback(fn() => $this->enabled);
        $helper->method('isSubmitEnabled')->willReturnCallback(fn() => $this->submitEnabled);
        $helper->method('requireApproval')->willReturnCallback(fn() => $this->requireApproval);
        $helper->method('isAssignSubmissionStore')->willReturnCallback(fn() => $this->assignStore);
        $helper->method('getBaseUrl')->willReturn('testimonials');

        $filter = $this->createStub(FilterManager::class);
        $filter->method('__call')->willReturnCallback(static function (string $name, array $args) {
            return match ($name) {
                'stripTags' => strip_tags((string) $args[0]),
                'translitUrl' => trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower((string) $args[0])), '-'),
                default => null,
            };
        });

        $cache = $this->createStub(CacheInterface::class);
        $cache->method('load')->willReturnCallback(fn() => $this->cachedAttempts);
        $cache->method('save')->willReturnCallback(function ($data, $id, $tags, $lifetime) {
            $this->cacheWrites[] = [$data, $id, $lifetime];
            return true;
        });

        $categoryCollection = $this->createStub(CategoryCollection::class);
        $categoryCollection->method('addFieldToFilter')->willReturnSelf();
        $categoryCollection->method('setPageSize')->willReturnSelf();
        $categoryCollection->method('getSize')->willReturnCallback(fn() => $this->categorySize);
        $categoryFactory = $this->createStub(CategoryCollectionFactory::class);
        $categoryFactory->method('create')->willReturn($categoryCollection);

        $testimonialCollection = $this->createStub(TestimonialCollection::class);
        $testimonialCollection->method('addFieldToFilter')->willReturnSelf();
        $testimonialCollection->method('setPageSize')->willReturnSelf();
        $testimonialCollection->method('getSize')->willReturnCallback(fn() => $this->urlKeySize);
        $testimonialFactory = $this->createStub(TestimonialCollectionFactory::class);
        $testimonialFactory->method('create')->willReturn($testimonialCollection);

        $store = $this->createStub(StoreInterface::class);
        $store->method('getId')->willReturn(3);
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        return new Save(
            $request,
            $redirectFactory,
            $messages,
            $validator,
            $factory,
            $resource,
            $helper,
            $filter,
            $jsonFactory,
            $cache,
            $categoryFactory,
            $testimonialFactory,
            $storeManager
        );
    }

    public function testDisabledSubmissionIsRejected(): void
    {
        $this->submitEnabled = false;
        $this->controller()->execute();

        $this->assertFalse($this->jsonData['success']);
        $this->assertSame('Testimonial submission is not available.', $this->jsonData['message']);
        $this->assertNull($this->saved);
    }

    public function testInvalidFormKeyIsRejectedWithRedirectForNonAjax(): void
    {
        $this->ajax = false;
        $this->formKeyValid = false;
        $this->controller()->execute();

        $this->assertSame('testimonials/submit', $this->redirectPath);
        $this->assertSame(['Invalid form submission. Please try again.'], $this->errorMessages);
        $this->assertNull($this->saved);
    }

    public function testHoneypotPretendsSuccessWithoutSaving(): void
    {
        $this->params['website_url'] = 'http://spam.test';
        $this->controller()->execute();

        $this->assertTrue($this->jsonData['success']);
        $this->assertNull($this->saved);
        $this->assertSame([], $this->cacheWrites);
    }

    public function testValidationErrorsAreCollected(): void
    {
        $this->params = ['customer_email' => 'not-an-email', 'content' => '<p></p>'];
        $this->controller()->execute();

        $this->assertFalse($this->jsonData['success']);
        $this->assertStringContainsString('Name is required.', $this->jsonData['message']);
        $this->assertStringContainsString('A valid email address is required.', $this->jsonData['message']);
        $this->assertStringContainsString('Title is required.', $this->jsonData['message']);
        $this->assertStringContainsString('Content is required.', $this->jsonData['message']);
        $this->assertNull($this->saved);
    }

    public function testOverlongFieldsAreRejected(): void
    {
        $this->params['customer_company'] = str_repeat('a', 256);
        $this->params['content'] = str_repeat('b', 5001);
        $this->controller()->execute();

        $this->assertStringContainsString('at most 255 characters', $this->jsonData['message']);
        $this->assertStringContainsString('at most 5000 characters', $this->jsonData['message']);
        $this->assertNull($this->saved);
    }

    public function testNonScalarParamIsTreatedAsEmpty(): void
    {
        $this->params['customer_name'] = ['x'];
        $this->controller()->execute();

        $this->assertSame('Name is required.', $this->jsonData['message']);
    }

    public function testRateLimitBlocksAfterFiveAttempts(): void
    {
        $this->cachedAttempts = '5';
        $this->controller()->execute();

        $this->assertFalse($this->jsonData['success']);
        $this->assertSame('Too many submissions. Please try again later.', $this->jsonData['message']);
        $this->assertNull($this->saved);
    }

    public function testSuccessfulSubmissionIsSavedAsPendingAndCountsAttempt(): void
    {
        $this->cachedAttempts = '2';
        $this->params['title'] = '<b>Great</b> Service';
        $this->params['category_id'] = '6';
        $this->categorySize = 1;
        $this->controller()->execute();

        $this->assertTrue($this->jsonData['success']);
        $this->assertStringContainsString('after review', $this->jsonData['message']);
        $this->assertNotNull($this->saved);
        $this->assertSame('Great Service', $this->saved->getTitle());
        $this->assertSame('great-service', $this->saved->getUrlKey());
        $this->assertSame(Testimonial::STATUS_PENDING, $this->saved->getStatus());
        $this->assertSame(4, $this->saved->getRating());
        $this->assertSame(0, $this->saved->getStoreId());
        $this->assertSame(6, $this->saved->getCategoryId());
        $this->assertSame('3', $this->cacheWrites[0][0]);
        $this->assertSame('panth_testimonial_submit_' . hash('sha256', '10.0.0.1'), $this->cacheWrites[0][1]);
        $this->assertSame(3600, $this->cacheWrites[0][2]);
    }

    public function testAutoApprovalAssignsStoreAndClampsRating(): void
    {
        $this->requireApproval = false;
        $this->assignStore = true;
        $this->params['rating'] = '9';
        $this->params['category_id'] = '6';
        $this->ajax = false;
        $this->controller()->execute();

        $this->assertSame(Testimonial::STATUS_APPROVED, $this->saved->getStatus());
        $this->assertSame(3, $this->saved->getStoreId());
        $this->assertSame(5, $this->saved->getRating());
        $this->assertNull($this->saved->getCategoryId(), 'inactive category is not assigned');
        $this->assertSame(['Thank you for your testimonial!'], $this->successMessages);
        $this->assertSame('testimonials', $this->redirectPath);
    }

    public function testDuplicateUrlKeyGetsRandomSuffixAndEmptyTransliterationFallsBack(): void
    {
        $this->urlKeySize = 1;
        $this->params['title'] = '!!!';
        $this->controller()->execute();

        $this->assertMatchesRegularExpression('/^testimonial-[0-9a-f]{8}$/', $this->saved->getUrlKey());
    }

    public function testLocalizedExceptionMessageIsShown(): void
    {
        $this->saveException = new LocalizedException(__('Title is required and cannot contain only HTML markup.'));
        $this->controller()->execute();

        $this->assertFalse($this->jsonData['success']);
        $this->assertSame('Title is required and cannot contain only HTML markup.', $this->jsonData['message']);
        $this->assertSame([], $this->cacheWrites);
    }

    public function testGenericExceptionIsMaskedAndNotCounted(): void
    {
        $this->saveException = new \RuntimeException('SQLSTATE secret');
        $this->controller()->execute();

        $this->assertFalse($this->jsonData['success']);
        $this->assertStringNotContainsString('SQLSTATE', $this->jsonData['message']);
        $this->assertSame([], $this->cacheWrites);
    }
}
