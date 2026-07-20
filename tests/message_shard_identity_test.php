<?php

declare(strict_types=1);

use B8im\ImShared\Support\MessageShardIdentity;

require dirname(__DIR__) . '/vendor/autoload.php';

$assertions = 0;
$assertSame = static function (mixed $expected, mixed $actual, string $message) use (&$assertions): void {
    if ($actual !== $expected) {
        throw new RuntimeException(sprintf(
            '%s: expected %s, got %s',
            $message,
            var_export($expected, true),
            var_export($actual, true),
        ));
    }
    ++$assertions;
};
$assertInvalid = static function (callable $callback, string $message) use (&$assertions): void {
    try {
        $callback();
    } catch (InvalidArgumentException) {
        ++$assertions;
        return;
    }

    throw new RuntimeException($message);
};

foreach ([
    [[1, str_repeat('a', 64), 1], 0],
    [[1, 'c1', 64], 0],
    [[7, 'single_abc', 64], 2],
    [[1, 'negative', 1024], 811],
    [[PHP_INT_MAX, 'group:tenant:conversation', 1024], 244],
] as [$arguments, $expectedBucket]) {
    $assertSame(
        $expectedBucket,
        MessageShardIdentity::bucket(...$arguments),
        'message shard bucket vector diverged',
    );
}

$assertSame(
    'im_message_0002_202607',
    MessageShardIdentity::tableName(7, 'single_abc', '2026-07-20 23:59:59', 64),
    'message shard table vector diverged',
);
$assertSame(
    'im_message_0811_200002',
    MessageShardIdentity::tableName(1, 'negative', '2000-02-29 00:00:00', 1024),
    'leap-day message shard table vector diverged',
);

foreach ([0, -1] as $organization) {
    $assertInvalid(
        static fn (): int => MessageShardIdentity::bucket($organization, 'conversation', 64),
        'invalid home organization was accepted',
    );
}
foreach ([
    '',
    ' conversation',
    'conversation ',
    "conversation\n",
    "conversation\0id",
    str_repeat('a', 65),
] as $conversationId) {
    $assertInvalid(
        static fn (): int => MessageShardIdentity::bucket(1, $conversationId, 64),
        'invalid conversation ID was accepted',
    );
}
foreach ([0, -1, 1025, PHP_INT_MAX] as $bucketCount) {
    $assertInvalid(
        static fn (): int => MessageShardIdentity::bucket(1, 'conversation', $bucketCount),
        'invalid message shard bucket count was accepted',
    );
}
foreach ([
    '',
    '2026-7-20 00:00:00',
    '2026-07-20',
    '2026-07-20T00:00:00',
    '2026-02-29 00:00:00',
    '2026-04-31 00:00:00',
    '2026-07-20 24:00:00',
    '2026-07-20 23:60:00',
    '2026-07-20 23:59:60',
    '2026-07-20 23:59:59 trailing',
] as $time) {
    $assertInvalid(
        static fn (): string => MessageShardIdentity::tableName(1, 'conversation', $time, 64),
        'invalid message time was accepted',
    );
}

fwrite(STDOUT, sprintf("Message shard identity: %d assertions passed.\n", $assertions));
