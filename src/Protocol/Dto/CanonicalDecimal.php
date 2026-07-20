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

    public static function increment(string $value, string $field): string
    {
        return self::add($value, '1', $field);
    }

    public static function add(string $left, string $right, string $field): string
    {
        self::nonNegative($left, $field);
        self::nonNegative($right, $field);

        $leftIndex = strlen($left) - 1;
        $rightIndex = strlen($right) - 1;
        $carry = 0;
        $result = '';
        while ($leftIndex >= 0 || $rightIndex >= 0 || $carry !== 0) {
            $sum = $carry;
            if ($leftIndex >= 0) {
                $sum += ord($left[$leftIndex--]) - 48;
            }
            if ($rightIndex >= 0) {
                $sum += ord($right[$rightIndex--]) - 48;
            }
            $result = (string) ($sum % 10) . $result;
            $carry = intdiv($sum, 10);
        }

        if (self::compare($result, self::UNSIGNED_BIGINT_MAX) > 0) {
            throw new \InvalidArgumentException($field . ' exceeds unsigned bigint');
        }

        return $result;
    }
}
