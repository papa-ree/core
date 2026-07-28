<?php

namespace Bale\Core\Support;

class Sanitize
{
    /**
     * Sanitize plain text input — strips tags, trims whitespace, normalizes characters.
     */
    public static function text(string $value): string
    {
        return self::normalizeWhitespace(
            strip_tags(trim($value))
        );
    }

    /**
     * Sanitize HTML input — allows a safe subset of tags.
     */
    public static function html(string $value, ?array $allowedTags = null): string
    {
        $allowed = $allowedTags ?? ['p', 'br', 'strong', 'em', 'ul', 'ol', 'li', 'a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'code', 'pre', 'img'];

        $value = trim($value);

        $value = strip_tags($value, implode('', array_map(fn ($tag) => "<{$tag}>", $allowed)));

        $value = self::normalizeWhitespace($value);

        return $value;
    }

    /**
     * Sanitize email address.
     */
    public static function email(string $value): string
    {
        $value = strtolower(trim($value));

        $value = filter_var($value, FILTER_SANITIZE_EMAIL);

        return $value ?: '';
    }

    /**
     * Sanitize URL — validates and filters.
     */
    public static function url(string $value): string
    {
        $value = trim($value);

        $value = filter_var($value, FILTER_SANITIZE_URL);

        return $value ?: '';
    }

    /**
     * Sanitize for use as a filename — removes special chars, normalizes.
     */
    public static function filename(string $value): string
    {
        $value = self::text($value);

        $value = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $value);

        $value = preg_replace('/_+/', '_', $value);

        $value = trim($value, '_.');

        return strtolower($value);
    }

    /**
     * Sanitize integer input.
     */
    public static function integer(string|int|float $value): int
    {
        return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Sanitize float input.
     */
    public static function float(string|int|float $value): float
    {
        return (float) filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }

    /**
     * Sanitize a phone number — keeps only digits and allowed prefixes.
     */
    public static function phone(string $value): string
    {
        $value = self::text($value);

        $value = preg_replace('/[^\d+\-\(\)\s]/', '', $value);

        $value = preg_replace('/\s+/', ' ', $value);

        return trim($value);
    }

    /**
     * Recursively sanitize all values in an array.
     */
    public static function array(array $data, string $type = 'text'): array
    {
        return array_map(function ($value) use ($type) {
            if (is_array($value)) {
                return self::array($value, $type);
            }

            if (! is_string($value)) {
                return $value;
            }

            return match ($type) {
                'html' => self::html($value),
                'email' => self::email($value),
                'url' => self::url($value),
                'filename' => self::filename($value),
                'integer' => self::integer($value),
                'float' => self::float($value),
                'phone' => self::phone($value),
                default => self::text($value),
            };
        }, $data);
    }

    /**
     * Normalize multiple consecutive whitespace characters into a single space.
     */
    protected static function normalizeWhitespace(string $value): string
    {
        return preg_replace('/\s+/', ' ', $value);
    }
}
