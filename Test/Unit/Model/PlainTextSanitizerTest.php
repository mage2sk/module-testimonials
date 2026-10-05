<?php
declare(strict_types=1);

namespace Panth\Testimonials\Test\Unit\Model;

use Panth\Testimonials\Model\PlainTextSanitizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PlainTextSanitizerTest extends TestCase
{
    private PlainTextSanitizer $sanitizer;

    protected function setUp(): void
    {
        $this->sanitizer = new PlainTextSanitizer();
    }

    public function testNullStaysNull(): void
    {
        $this->assertNull($this->sanitizer->sanitize(null));
        $this->assertNull($this->sanitizer->sanitizeAttributeSafe(null));
    }

    #[DataProvider('sanitizeCases')]
    public function testSanitize(string $input, string $expected): void
    {
        $this->assertSame($expected, $this->sanitizer->sanitize($input));
    }

    public static function sanitizeCases(): array
    {
        return [
            'plain text untouched' => ['Great service', 'Great service'],
            'trimmed' => ["  hello \n", 'hello'],
            'tags stripped' => ['<b>Bold</b> text', 'Bold text'],
            'script tags removed' => ['<script>alert(1)</script>Hi', 'alert(1)Hi'],
            'entity-encoded tags decoded and stripped' => ['&lt;img src=x onerror=alert(1)&gt;Hi', 'Hi'],
            'double-encoded tags stripped' => ['&amp;lt;b&amp;gt;x&amp;lt;/b&amp;gt;', 'x'],
            'entities decoded to plain chars' => ['Tom &amp; Jerry', 'Tom & Jerry'],
            'only markup becomes empty' => ['<p></p>', ''],
        ];
    }

    public function testDeeplyNestedEncodingNeverLeaksMarkupCharacters(): void
    {
        $value = '<b>x</b>';
        for ($i = 0; $i < 7; $i++) {
            $value = htmlspecialchars($value, ENT_QUOTES);
        }

        $result = $this->sanitizer->sanitize($value);

        $this->assertStringNotContainsString('<', $result);
        $this->assertStringNotContainsString('>', $result);
        $this->assertStringNotContainsString('&', $result);
    }

    public function testAttributeSafeReplacesDoubleQuotes(): void
    {
        $this->assertSame("John 'JJ' Doe", $this->sanitizer->sanitizeAttributeSafe('John "JJ" Doe'));
        $this->assertSame("a 'b'", $this->sanitizer->sanitizeAttributeSafe(' <i>a</i> &quot;b&quot; '));
    }
}
