<?php

declare(strict_types=1);

namespace B8im\ImShared\Support;

/** Canonical message shard bucket and physical table identity. */
final class MessageShardIdentity
{
    public const MIN_BUCKET_COUNT = 1;
    public const MAX_BUCKET_COUNT = 1024;

    public static function assertValidBucketCount(int $bucketCount): void
    {
        if ($bucketCount < self::MIN_BUCKET_COUNT || $bucketCount > self::MAX_BUCKET_COUNT) {
            throw new \InvalidArgumentException('message shard bucket count must be between 1 and 1024');
        }
    }

    public static function bucket(
        int $homeOrganization,
        string $conversationId,
        int $bucketCount,
    ): int {
        self::assertValidIdentity($homeOrganization, $conversationId);
        self::assertValidBucketCount($bucketCount);

        // PHP uses an int return type for crc32(); normalize it to UINT32 before
        // applying the canonical abs/modulo algorithm on 64-bit runtimes.
        $unsignedChecksum = (int) sprintf(
            '%u',
            crc32($homeOrganization . ':' . $conversationId),
        );

        return abs($unsignedChecksum) % $bucketCount;
    }

    public static function tableName(
        int $homeOrganization,
        string $conversationId,
        string $time,
        int $bucketCount,
    ): string {
        $month = self::month($time);
        $bucket = self::bucket($homeOrganization, $conversationId, $bucketCount);

        return sprintf('im_message_%04d_%s', $bucket, $month);
    }

    private static function assertValidIdentity(int $homeOrganization, string $conversationId): void
    {
        if ($homeOrganization <= 0) {
            throw new \InvalidArgumentException('home organization must be positive');
        }
        if (
            $conversationId === ''
            || strlen($conversationId) > 64
            || trim($conversationId) !== $conversationId
            || preg_match('/[\x00-\x1f\x7f]/', $conversationId) === 1
        ) {
            throw new \InvalidArgumentException(
                'conversation ID must contain 1..64 bytes without surrounding whitespace or control characters',
            );
        }
    }

    private static function month(string $time): string
    {
        if (
            preg_match(
                '/^(\d{4})-(\d{2})-(\d{2}) ([01]\d|2[0-3]):([0-5]\d):([0-5]\d)$/D',
                $time,
                $parts,
            ) !== 1
            || !checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])
        ) {
            throw new \InvalidArgumentException('message time must be a valid Y-m-d H:i:s value');
        }

        return $parts[1] . $parts[2];
    }
}
