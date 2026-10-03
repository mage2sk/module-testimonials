<?php
declare(strict_types=1);

namespace Panth\Testimonials\Model;

class ImageUrlResolver
{
    public static function resolve(?string $value, string $mediaBaseUrl): ?string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }
        if (preg_match('#^https?://#i', $value) || str_starts_with($value, '/')) {
            return str_starts_with($value, '//') ? null : $value;
        }
        if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $value)) {
            return null;
        }
        $path = ltrim($value, '/');
        foreach (['pub/media/', 'media/'] as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $path = substr($path, strlen($prefix));
                break;
            }
        }

        return rtrim($mediaBaseUrl, '/') . '/' . $path;
    }
}
