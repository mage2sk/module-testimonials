<?php
declare(strict_types=1);

namespace Panth\Testimonials\Test\Unit\Model;

use Panth\Testimonials\Model\ImageUrlResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ImageUrlResolverTest extends TestCase
{
    private const MEDIA = 'https://shop.test/media/';

    #[DataProvider('cases')]
    public function testResolve(?string $value, ?string $expected): void
    {
        $this->assertSame($expected, ImageUrlResolver::resolve($value, self::MEDIA));
    }

    public static function cases(): array
    {
        return [
            'null' => [null, null],
            'empty' => ['', null],
            'whitespace' => ['   ', null],
            'absolute https kept' => ['https://cdn.test/a.jpg', 'https://cdn.test/a.jpg'],
            'absolute http upper-case scheme kept' => ['HTTP://cdn.test/a.jpg', 'HTTP://cdn.test/a.jpg'],
            'root relative kept' => ['/media/x.png', '/media/x.png'],
            'protocol relative rejected' => ['//evil.test/x.png', null],
            'javascript scheme rejected' => ['javascript:alert(1)', null],
            'data uri rejected' => ['data:image/png;base64,AAA', null],
            'relative path prefixed' => ['testimonials/a.jpg', 'https://shop.test/media/testimonials/a.jpg'],
            'media prefix stripped' => ['media/testimonials/a.jpg', 'https://shop.test/media/testimonials/a.jpg'],
            'pub media prefix stripped' => ['pub/media/testimonials/a.jpg', 'https://shop.test/media/testimonials/a.jpg'],
            'trimmed' => ['  a.jpg  ', 'https://shop.test/media/a.jpg'],
        ];
    }

    public function testMediaBaseWithoutTrailingSlashIsJoinedWithSingleSlash(): void
    {
        $this->assertSame('https://m.test/x/a.jpg', ImageUrlResolver::resolve('a.jpg', 'https://m.test/x'));
        $this->assertSame('https://m.test/x/a.jpg', ImageUrlResolver::resolve('a.jpg', 'https://m.test/x///'));
    }
}
