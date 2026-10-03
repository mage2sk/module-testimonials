<?php
declare(strict_types=1);

namespace Panth\Testimonials\Model;

class PlainTextSanitizer
{
    private const MAX_PASSES = 5;

    public function sanitize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        for ($pass = 0; $pass < self::MAX_PASSES; $pass++) {
            $clean = strip_tags(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($clean === $value) {
                break;
            }
            $value = $clean;
        }

        if (strip_tags(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8')) !== $value) {
            $value = str_replace(['<', '>', '&'], '', $value);
        }

        return trim($value);
    }

    public function sanitizeAttributeSafe(?string $value): ?string
    {
        $value = $this->sanitize($value);

        return $value === null ? null : trim(str_replace('"', "'", $value));
    }
}
