<?php
declare(strict_types=1);

namespace Panth\Testimonials\Controller\Submit;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filter\FilterManager;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Panth\Testimonials\Helper\Data as DataHelper;
use Panth\Testimonials\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Panth\Testimonials\Model\ResourceModel\Testimonial\CollectionFactory as TestimonialCollectionFactory;
use Panth\Testimonials\Model\TestimonialFactory;
use Panth\Testimonials\Model\ResourceModel\Testimonial as TestimonialResource;
use Panth\Testimonials\Model\Testimonial;

class Save implements HttpPostActionInterface
{
    private const MAX_FIELD_LENGTH = 255;
    private const MAX_CONTENT_LENGTH = 5000;
    private const RATE_LIMIT_MAX = 5;
    private const RATE_LIMIT_WINDOW = 3600;

    public function __construct(
        private readonly RequestInterface $request,
        private readonly RedirectFactory $redirectFactory,
        private readonly MessageManagerInterface $messageManager,
        private readonly FormKeyValidator $formKeyValidator,
        private readonly TestimonialFactory $testimonialFactory,
        private readonly TestimonialResource $testimonialResource,
        private readonly DataHelper $helper,
        private readonly FilterManager $filterManager,
        private readonly JsonFactory $jsonFactory,
        private readonly CacheInterface $cache,
        private readonly CategoryCollectionFactory $categoryCollectionFactory,
        private readonly TestimonialCollectionFactory $testimonialCollectionFactory,
        private readonly StoreManagerInterface $storeManager
    ) {}

    public function execute(): ResultInterface
    {
        if (!$this->helper->isEnabled() || !$this->helper->isSubmitEnabled()) {
            return $this->respond(false, [__('Testimonial submission is not available.')], '');
        }

        if (!$this->formKeyValidator->validate($this->request)) {
            return $this->respond(false, [__('Invalid form submission. Please try again.')], '/submit');
        }

        $honeypot = trim((string) $this->request->getParam('website_url'));
        if (!empty($honeypot)) {
            return $this->respond(true, [__('Thank you for your testimonial!')], '');
        }

        $clientIp = $this->request->getClientIp() ?: 'unknown';
        $rateLimitKey = 'panth_testimonial_submit_' . hash('sha256', (string) $clientIp);

        $customerName = $this->getCleanParam('customer_name');
        $customerEmail = $this->getCleanParam('customer_email');
        $title = $this->getCleanParam('title');
        $content = $this->getCleanParam('content');
        $customerTitle = $this->getCleanParam('customer_title');
        $customerCompany = $this->getCleanParam('customer_company');
        $rating = (int) $this->request->getParam('rating', 5);
        $categoryId = (int) $this->request->getParam('category_id');

        $errors = [];
        if (empty($customerName)) {
            $errors[] = __('Name is required.');
        }
        if (empty($customerEmail) || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = __('A valid email address is required.');
        }
        if (empty($title)) {
            $errors[] = __('Title is required.');
        }
        if (empty($content)) {
            $errors[] = __('Content is required.');
        }
        foreach ([$customerName, $customerEmail, $title, $customerTitle, $customerCompany] as $value) {
            if (mb_strlen($value) > self::MAX_FIELD_LENGTH) {
                $errors[] = __('Each field can contain at most %1 characters.', self::MAX_FIELD_LENGTH);
                break;
            }
        }
        if (mb_strlen($content) > self::MAX_CONTENT_LENGTH) {
            $errors[] = __('The testimonial can contain at most %1 characters.', self::MAX_CONTENT_LENGTH);
        }
        if ($rating < 1 || $rating > 5) {
            $rating = 5;
        }

        if (!empty($errors)) {
            return $this->respond(false, $errors, '/submit');
        }

        $attempts = (int) $this->cache->load($rateLimitKey);
        if ($attempts >= self::RATE_LIMIT_MAX) {
            return $this->respond(false, [__('Too many submissions. Please try again later.')], '/submit');
        }

        $storeId = (int) $this->storeManager->getStore()->getId();
        $requireApproval = $this->helper->requireApproval($storeId);

        try {
            $urlKey = (string) $this->filterManager->translitUrl($title);
            if ($urlKey === '') {
                $urlKey = 'testimonial';
            }

            $testimonial = $this->testimonialFactory->create();
            $testimonial->setCustomerName($customerName)
                        ->setCustomerEmail($customerEmail)
                        ->setTitle($title)
                        ->setContent($content)
                        ->setCustomerTitle($customerTitle)
                        ->setCustomerCompany($customerCompany)
                        ->setRating($rating)
                        ->setUrlKey($this->getUniqueUrlKey($urlKey))
                        ->setStatus($requireApproval ? Testimonial::STATUS_PENDING : Testimonial::STATUS_APPROVED)
                        ->setStoreId($this->helper->isAssignSubmissionStore($storeId) ? $storeId : 0);

            if ($categoryId > 0 && $this->isActiveCategory($categoryId)) {
                $testimonial->setCategoryId($categoryId);
            }

            $this->testimonialResource->save($testimonial);
            $this->cache->save((string) ($attempts + 1), $rateLimitKey, [], self::RATE_LIMIT_WINDOW);
        } catch (LocalizedException $e) {
            return $this->respond(false, [$e->getMessage()], '/submit');
        } catch (\Exception $e) {
            return $this->respond(
                false,
                [__('An error occurred while saving your testimonial. Please try again.')],
                '/submit'
            );
        }

        if ($requireApproval) {
            return $this->respond(
                true,
                [__('Thank you for your testimonial! It will be published after review.')],
                ''
            );
        }

        return $this->respond(true, [__('Thank you for your testimonial!')], '');
    }

    private function getCleanParam(string $name): string
    {
        $value = $this->request->getParam($name, '');
        if (!is_scalar($value)) {
            return '';
        }

        return trim((string) $this->filterManager->stripTags(trim((string) $value)));
    }

    private function respond(bool $success, array $messages, string $pathSuffix): ResultInterface
    {
        if ($this->request->isXmlHttpRequest()) {
            return $this->jsonFactory->create()->setData([
                'success' => $success,
                'message' => implode(' ', array_map('strval', $messages)),
            ]);
        }

        foreach ($messages as $message) {
            if ($success) {
                $this->messageManager->addSuccessMessage($message);
            } else {
                $this->messageManager->addErrorMessage($message);
            }
        }

        return $this->redirectFactory->create()->setPath($this->helper->getBaseUrl() . $pathSuffix);
    }

    private function isActiveCategory(int $categoryId): bool
    {
        $collection = $this->categoryCollectionFactory->create();
        $collection->addFieldToFilter('category_id', $categoryId)
                   ->addFieldToFilter('is_active', 1)
                   ->setPageSize(1);

        return $collection->getSize() > 0;
    }

    private function getUniqueUrlKey(string $urlKey): string
    {
        $urlKey = mb_substr($urlKey, 0, 200);
        $collection = $this->testimonialCollectionFactory->create();
        $collection->addFieldToFilter('url_key', $urlKey)->setPageSize(1);
        if ($collection->getSize() > 0) {
            return $urlKey . '-' . substr(hash('sha256', uniqid('', true)), 0, 8);
        }

        return $urlKey;
    }
}
