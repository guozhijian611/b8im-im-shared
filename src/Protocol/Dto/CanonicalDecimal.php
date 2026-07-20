<?php

declare(strict_types=1);

namespace B8im\ImShared\Protocol\Dto;

final class CanonicalDecimal
{
    public const UNSIGNED_BIGINT_MAX = '18446744073709551615';

    public static function positive(string $value, string $field): string
    {
        if (
            preg_match('/^[1-9][0-9]{0,19}$/D', $value) !== 1
            || self::compare($value, self::UNSIGNED_BIGINT_MAX) > 0
        ) {
            throw new \InvalidArgumentException($field . ' must be a canonical positive decimal string');
        }

        return $value;
    }

    public static function nonNegative(string $value, string $field): string
    {
        if (
            preg_match('/^(0|[1-9][0-9]{0,19})$/D', $value) !== 1
            || self::compare($value, self::UNSIGNED_BIGINT_MAX) > 0
        ) {
            throw new \InvalidArgumentException($field . ' must be a canonical non-negative decimal string');
        }

        return $value;
    }

    public static function compare(string $left, string $right): int
    {
        $length = strlen($left) <=> strlen($right);

        return $length !== 0 ? $length : strcmp($left, $right);
    }
}
