<?php

declare(strict_types=1);

use B8im\ImShared\Protocol\Dto\CanonicalDecimal;
use B8im\ImShared\Protocol\Dto\SearchProjectionEvent;

require dirname(__DIR__) . '/vendor/autoload.php';

$assertions = 0;
$assert = static function (bool $condition, string $message) use (&$assertions): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
    ++$assertions;
};
$reject = static function (callable $operation, string $message) use ($assert): void {
    try {
        $operation();
    } catch (InvalidArgumentException) {
        $assert(true, $message);
        return;
    }
    throw new RuntimeException($message);
};

foreach (SearchProjectionEvent::EVENT_TYPES as $eventType) {
    $event = new SearchProjectionEvent(
        str_repeat('a', 64),
        7,
        $eventType,
        CanonicalDecimal::UNSIGNED_BIGINT_MAX,
        '01JZ-message.id:01',
    );
    $assert($event->toArray() === [
        'event_contract' => 'im.search-projection.v1',
        'event_id' => str_repeat('a', 64),
        'organization' => 7,
        'event_type' => $eventType,
        'source_event_seq' => '18446744073709551615',
        'message_id' => '01JZ-message.id:01',
    ], $eventType . ' changed the canonical search projection contract');
    $assert(
        SearchProjectionEvent::fromArray($event->toArray())->toArray() === $event->toArray(),
        $eventType . ' failed strict contract round-trip',
    );
}

foreach (['0', '01', '18446744073709551616'] as $sourceEventSeq) {
    $reject(
        static fn () => new SearchProjectionEvent(
            str_repeat('a', 64),
            7,
            SearchProjectionEvent::EVENT_CREATED,
            $sourceEventSeq,
            'message-1',
        ),
        'invalid source_event_seq was accepted',
    );
}

foreach ([
    ['0', '0', '0'],
    ['0', '1', '1'],
    ['9', '1', '10'],
    ['9999999999999999999', '1', '10000000000000000000'],
    ['18446744073709551614', '1', CanonicalDecimal::UNSIGNED_BIGINT_MAX],
    ['18446744073709551600', '15', CanonicalDecimal::UNSIGNED_BIGINT_MAX],
] as [$left, $right, $expected]) {
    $assert(
        CanonicalDecimal::add($left, $right, 'uint64_test') === $expected,
        'canonical uint64 decimal addition failed',
    );
}
$assert(
    CanonicalDecimal::increment('9999999999999999999', 'uint64_test')
        === '10000000000000000000',
    'canonical uint64 decimal increment failed across a 19-digit carry',
);
foreach ([
    static fn () => CanonicalDecimal::increment(CanonicalDecimal::UNSIGNED_BIGINT_MAX, 'uint64_test'),
    static fn () => CanonicalDecimal::add(CanonicalDecimal::UNSIGNED_BIGINT_MAX, '1', 'uint64_test'),
    static fn () => CanonicalDecimal::add('01', '1', 'uint64_test'),
    static fn () => CanonicalDecimal::add('1', '01', 'uint64_test'),
] as $invalidAddition) {
    $reject($invalidAddition, 'invalid or overflowing canonical uint64 addition was accepted');
}

$valid = (new SearchProjectionEvent(
    str_repeat('b', 64),
    1,
    SearchProjectionEvent::EVENT_CREATED,
    '1',
    str_repeat('m', 64),
))->toArray();
foreach (SearchProjectionEvent::FIELDS as $field) {
    $missing = $valid;
    unset($missing[$field]);
    $reject(
        static fn () => SearchProjectionEvent::fromArray($missing),
        'missing search projection field was accepted: ' . $field,
    );
}
$reject(
    static fn () => SearchProjectionEvent::fromArray([...$valid, 'legacy_seq' => '1']),
    'additional legacy search projection field was accepted',
);
$reject(
    static fn () => SearchProjectionEvent::fromArray([
        ...$valid,
        'event_contract' => 'im.search-projection.v0',
    ]),
    'legacy event contract was accepted',
);
$reject(
    static fn () => new SearchProjectionEvent(
        str_repeat('a', 64),
        7,
        'message.deleted_self',
        '1',
        'message-1',
    ),
    'non-search mutation was accepted',
);

fwrite(STDOUT, "Search projection event contract: {$assertions} assertions passed.\n");
