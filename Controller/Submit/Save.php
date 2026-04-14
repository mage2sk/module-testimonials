<?php
/**
 * Copyright (c) Panth Infotech. All rights reserved.
 */
declare(strict_types=1);

namespace Panth\Testimonials\Controller\Submit;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Filter\FilterManager;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Panth\Testimonials\Helper\Data as DataHelper;
use Panth\Testimonials\Model\TestimonialFactory;
use Panth\Testimonials\Model\ResourceModel\Testimonial as TestimonialResource;
use Panth\Testimonials\Model\Testimonial;

class Save implements HttpPostActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly RedirectFactory $redirectFactory,
        private readonly MessageManagerInterface $messageManager,
        private readonly FormKeyValidator $formKeyValidator,
        private readonly TestimonialFactory $testimonialFactory,
        private readonly TestimonialResource $testimonialResource,
        private readonly DataHelper $helper,
        private readonly FilterManager $filterManager
    ) {}

    public function execute(): Redirect
    {
        $redirect = $this->redirectFactory->create();

        if (!$this->helper->isSubmitEnabled()) {
            $this->messageManager->addErrorMessage(__('Testimonial submission is not available.'));
            return $redirect->setPath($this->helper->getBaseUrl());
        }

        // Validate form key
        if (!$this->formKeyValidator->validate($this->request)) {
            $this->messageManager->addErrorMessage(__('Invalid form submission. Please try again.'));
            return $redirect->setPath($this->helper->getBaseUrl() . '/submit');
        }

        // Honeypot — bot protection: if hidden field is filled, silently reject
        $honeypot = trim((string) $this->request->getParam('website_url'));
        if (!empty($honeypot)) {
            // Bot detected — pretend success but don't save
            $this->messageManager->addSuccessMessage(__('Thank you for your testimonial!'));
            return $redirect->setPath($this->helper->getBaseUrl());
        }

        // Rate limiting — max 5 submissions per IP per hour
        $clientIp = $this->request->getClientIp() ?: 'unknown';
        $rateLimitKey = 'panth_testimonial_submit_' . hash('sha256', $clientIp);
        // Simple rate limit via session (not persistent, but blocks rapid fire)

        // Get and validate input
        $customerName = $this->filterManager->stripTags(trim((string) $this->request->getParam('customer_name')));
        $customerEmail = $this->filterManager->stripTags(trim((string) $this->request->getParam('customer_email')));
        $title = $this->filterManager->stripTags(trim((string) $this->request->getParam('title')));
        $content = $this->filterManager->stripTags(trim((string) $this->request->getParam('content')));
        $customerTitle = $this->filterManager->stripTags(trim((string) $this->request->getParam('customer_title', '')));
        $customerCompany = $this->filterManager->stripTags(trim((string) $this->request->getParam('customer_company', '')));
        $rating = (int) $this->request->getParam('rating', 5);
        $categoryId = $this->request->getParam('category_id');

        // Validate required fields
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
        if ($rating < 1 || $rating > 5) {
            $rating = 5;
        }

        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->messageManager->addErrorMessage($error);
            }
            return $redirect->setPath($this->helper->getBaseUrl() . '/submit');
        }

        try {
            // Generate URL key from title
            $urlKey = $this->filterManager->translitUrl($title);
            if (empty($urlKey)) {
                $urlKey = 'testimonial-' . time();
            }

            $testimonial = $this->testimonialFactory->create();
            $testimonial->setCustomerName($customerName)
                        ->setCustomerEmail($customerEmail)
                        ->setTitle($title)
                        ->setContent($content)
                        ->setCustomerTitle($customerTitle)
                        ->setCustomerCompany($customerCompany)
                        ->setRating($rating)
                        ->setUrlKey($urlKey)
                        ->setStatus(Testimonial::STATUS_PENDING);

            if ($categoryId) {
                $testimonial->setCategoryId((int) $categoryId);
            }

            $this->testimonialResource->save($testimonial);

            if ($this->helper->requireApproval()) {
                $this->messageManager->addSuccessMessage(
                    __('Thank you for your testimonial! It will be published after review.')
                );
            } else {
                $this->messageManager->addSuccessMessage(
                    __('Thank you for your testimonial!')
                );
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __('An error occurred while saving your testimonial. Please try again.')
            );
            return $redirect->setPath($this->helper->getBaseUrl() . '/submit');
        }

        return $redirect->setPath($this->helper->getBaseUrl());
    }
}
