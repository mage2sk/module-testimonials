<?php
/**
 * Copyright (c) Panth Infotech. All rights reserved.
 *
 * Custom router for testimonial URLs:
 *   /testimonials           → Index/Index
 *   /testimonials/submit    → Submit/Index
 *   /testimonials/category/{url_key} → Category/View
 *   /testimonials/{url_key} → View/Index
 */
declare(strict_types=1);

namespace Panth\Testimonials\Controller;

use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\RouterInterface;
use Panth\Testimonials\Helper\Data as DataHelper;
use Panth\Testimonials\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Panth\Testimonials\Model\ResourceModel\Testimonial\CollectionFactory as TestimonialCollectionFactory;
use Panth\Testimonials\Model\Testimonial;

class Router implements RouterInterface
{
    public function __construct(
        private readonly ActionFactory $actionFactory,
        private readonly DataHelper $helper,
        private readonly CategoryCollectionFactory $categoryCollectionFactory,
        private readonly TestimonialCollectionFactory $testimonialCollectionFactory
    ) {}

    public function match(RequestInterface $request): ?\Magento\Framework\App\ActionInterface
    {
        if (!$this->helper->isEnabled()) {
            return null;
        }

        $identifier = trim($request->getPathInfo(), '/');
        $baseRoute = $this->helper->getBaseUrl();

        // Exact match: /testimonials
        if ($identifier === $baseRoute) {
            $request->setModuleName('testimonials')
                    ->setControllerName('index')
                    ->setActionName('index');
            return $this->actionFactory->create(\Magento\Framework\App\Action\Forward::class);
        }

        // Must start with base route
        if (!str_starts_with($identifier, $baseRoute . '/')) {
            return null;
        }

        $pathSuffix = substr($identifier, strlen($baseRoute) + 1);
        $parts = explode('/', $pathSuffix);

        // /testimonials/page/{num} → listing with page param
        if ($parts[0] === 'page' && isset($parts[1]) && is_numeric($parts[1])) {
            $request->setModuleName('testimonials')
                    ->setControllerName('index')
                    ->setActionName('index')
                    ->setParam('p', (int)$parts[1]);
            return $this->actionFactory->create(\Magento\Framework\App\Action\Forward::class, ['request' => $request]);
        }

        // /testimonials/submit
        if ($parts[0] === 'submit' && count($parts) === 1) {
            $request->setModuleName('testimonials')
                    ->setControllerName('submit')
                    ->setActionName('index');
            return $this->actionFactory->create(\Magento\Framework\App\Action\Forward::class);
        }

        // /testimonials/submit/save (POST handler) — let standard router handle
        if ($parts[0] === 'submit' && isset($parts[1]) && $parts[1] === 'save') {
            return null;
        }

        // /testimonials/category/view, /testimonials/view/index etc — let standard router handle
        if (count($parts) >= 2 && in_array($parts[1], ['view', 'index', 'save', 'delete', 'edit', 'new'])) {
            return null;
        }

        // /testimonials/category/{url_key}
        if ($parts[0] === 'category' && isset($parts[1]) && $parts[1] !== '') {
            $request->setModuleName('testimonials')
                    ->setControllerName('category')
                    ->setActionName('view')
                    ->setParam('url_key', $parts[1]);
            return $this->actionFactory->create(\Magento\Framework\App\Action\Forward::class, ['request' => $request]);
        }

        // /testimonials/{url_key} — check category first, then testimonial
        if (count($parts) === 1 && $parts[0] !== '') {
            $slug = $parts[0];

            // Check if slug is a category url_key
            $categoryCollection = $this->categoryCollectionFactory->create();
            $categoryCollection->addFieldToFilter('url_key', $slug)
                              ->addFieldToFilter('is_active', 1)
                              ->setPageSize(1);

            if ($categoryCollection->getSize() > 0) {
                $request->setModuleName('testimonials')
                        ->setControllerName('category')
                        ->setActionName('view')
                        ->setParam('url_key', $slug);
                return $this->actionFactory->create(\Magento\Framework\App\Action\Forward::class, ['request' => $request]);
            }

            // Check if slug is a testimonial url_key
            $testimonialCollection = $this->testimonialCollectionFactory->create();
            $testimonialCollection->addFieldToFilter('url_key', $slug)
                                 ->addFieldToFilter('status', Testimonial::STATUS_APPROVED)
                                 ->setPageSize(1);

            if ($testimonialCollection->getSize() > 0) {
                $request->setModuleName('testimonials')
                        ->setControllerName('view')
                        ->setActionName('index')
                        ->setParam('url_key', $slug);
                return $this->actionFactory->create(\Magento\Framework\App\Action\Forward::class, ['request' => $request]);
            }
        }

        return null;
    }
}
