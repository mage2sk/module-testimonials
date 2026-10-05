<?php
declare(strict_types=1);

namespace Panth\Testimonials\Test\Unit\Controller\Adminhtml;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\Request\Http;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Message\ManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Shared backend action plumbing: captures redirects, messages and url-key lookups.
 */
abstract class AdminControllerTestCase extends TestCase
{
    protected array|false $post = false;
    protected array $params = [];
    protected ?array $redirect = null;
    protected array $successMessages = [];
    protected array $errorMessages = [];
    /** @var array<int, string|false> url-key lookup results, consumed in order */
    protected array $fetchResults = [];
    protected array $lookedUpKeys = [];

    protected function backendContext(): Context
    {
        $request = $this->createStub(Http::class);
        $request->method('getPostValue')->willReturnCallback(fn() => $this->post);
        $request->method('getParam')->willReturnCallback(
            fn(string $key, $default = null) => $this->params[$key] ?? $default
        );

        $redirect = $this->createStub(Redirect::class);
        $redirect->method('setPath')->willReturnCallback(function (string $path, array $params = []) use ($redirect) {
            $this->redirect = [$path, $params];
            return $redirect;
        });
        $redirectFactory = $this->createStub(RedirectFactory::class);
        $redirectFactory->method('create')->willReturn($redirect);

        $messages = $this->createStub(ManagerInterface::class);
        $messages->method('addSuccessMessage')->willReturnCallback(function ($m) use ($messages) {
            $this->successMessages[] = (string) $m;
            return $messages;
        });
        $messages->method('addErrorMessage')->willReturnCallback(function ($m) use ($messages) {
            $this->errorMessages[] = (string) $m;
            return $messages;
        });

        $context = $this->createStub(Context::class);
        $context->method('getRequest')->willReturn($request);
        $context->method('getResultRedirectFactory')->willReturn($redirectFactory);
        $context->method('getMessageManager')->willReturn($messages);

        return $context;
    }

    protected function connection(): AdapterInterface
    {
        $select = $this->createStub(Select::class);
        $select->method('from')->willReturnSelf();
        $select->method('limit')->willReturnSelf();
        $select->method('where')->willReturnCallback(function (string $cond, $value = null) use ($select) {
            if ($cond === 'url_key = ?') {
                $this->lookedUpKeys[] = $value;
            }
            return $select;
        });

        $connection = $this->createStub(AdapterInterface::class);
        $connection->method('select')->willReturn($select);
        $connection->method('fetchOne')->willReturnCallback(
            fn() => $this->fetchResults === [] ? false : array_shift($this->fetchResults)
        );

        return $connection;
    }
}
