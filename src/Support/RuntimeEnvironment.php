<?php

declare(strict_types=1);

namespace B8im\ImShared\Support;

use InvalidArgumentException;

final class RuntimeEnvironment
{
    public const DEFAULT_TIMEZONE = 'Asia/Shanghai';

    public static function value(string $key, ?string $default = null): ?string
    {
        $value = getenv($key);
        if ($value === false || $value === null) {
            $value = $_ENV[$key] ?? $_SERVER[$key] ?? $default;
        }

        return $value === null ? null : trim((string) $value);
    }

    public static function configureTimezone(?string $timezone = null): string
    {
        $timezone = trim((string) $timezone);
        if ($timezone === '') {
            $timezone = self::DEFAULT_TIMEZONE;
        }
        if (!@date_default_timezone_set($timezone)) {
            throw new InvalidArgumentException('IM_TIMEZONE is not a valid IANA timezone.');
        }

        return $timezone;
    }

    public static function requireInternalSecret(?string $secret): string
    {
        return self::requireSecret($secret, 'SECRET_KEY');
    }

    public static function requireSecret(?string $secret, string $name): string
    {
        $secret = trim((string) $secret);
        $normalized = strtolower($secret);
        $name = trim($name);
        if ($name === '' || preg_match('/^[A-Z][A-Z0-9_]*$/', $name) !== 1) {
            throw new InvalidArgumentException('secret name must be an uppercase environment variable name.');
        }
        if (
            strlen($secret) < 32
            || str_contains($normalized, 'please-change')
            || str_contains($normalized, 'change-me')
            || str_contains($normalized, 'changeme')
            || str_contains($normalized, 'example-secret')
            || str_contains($normalized, 'example_')
            || str_contains($normalized, 'sample-secret')
            || str_contains($normalized, 'placeholder')
            || str_contains($normalized, 'replace-with')
        ) {
            throw new InvalidArgumentException(
                $name . ' must be a non-placeholder secret of at least 32 bytes.',
            );
        }

        return $secret;
    }
}
