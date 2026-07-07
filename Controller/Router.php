<?php
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

        if ($identifier === $baseRoute) {
            $request->setModuleName('testimonials')
                    ->setControllerName('index')
                    ->setActionName('index');
            return $this->actionFactory->create(\Magento\Framework\App\Action\Forward::class);
        }

        if (!str_starts_with($identifier, $baseRoute . '/')) {
            return null;
        }

        $pathSuffix = substr($identifier, strlen($baseRoute) + 1);
        $parts = explode('/', $pathSuffix);

        if ($parts[0] === 'page' && isset($parts[1]) && is_numeric($parts[1])) {
            $request->setModuleName('testimonials')
                    ->setControllerName('index')
                    ->setActionName('index')
                    ->setParam('p', (int)$parts[1]);
            return $this->actionFactory->create(\Magento\Framework\App\Action\Forward::class, ['request' => $request]);
        }

        if ($parts[0] === 'submit' && count($parts) === 1) {
            $request->setModuleName('testimonials')
                    ->setControllerName('submit')
                    ->setActionName('index');
            return $this->actionFactory->create(\Magento\Framework\App\Action\Forward::class);
        }

        if ($parts[0] === 'submit' && isset($parts[1]) && $parts[1] === 'save') {
            return null;
        }

        if (count($parts) >= 2 && in_array($parts[1], ['view', 'index', 'save', 'delete', 'edit', 'new'])) {
            return null;
        }

        if ($parts[0] === 'category' && isset($parts[1]) && $parts[1] !== '') {
            $request->setModuleName('testimonials')
                    ->setControllerName('category')
                    ->setActionName('view')
                    ->setParam('url_key', $parts[1]);
            return $this->actionFactory->create(\Magento\Framework\App\Action\Forward::class, ['request' => $request]);
        }

        if (count($parts) === 1 && $parts[0] !== '') {
            $slug = $parts[0];

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
