<?php

declare(strict_types=1);

use B8im\ImShared\Protocol\Packet;
use B8im\ImShared\Protocol\MessageType;
use B8im\ImShared\Protocol\Command;
use B8im\ImShared\Protocol\Dto\GroupMemberAccessEntry;
use B8im\ImShared\Protocol\Dto\GroupMemberAccessChanged;
use B8im\ImShared\Protocol\Dto\GroupMemberAccessPeriod;
use B8im\ImShared\Protocol\Dto\CanonicalDecimal;
use B8im\ImShared\Protocol\Dto\GroupMemberAccessSnapshotRequest;
use B8im\ImShared\Protocol\Dto\GroupMemberAccessSnapshotPage;
use B8im\ImShared\Support\Constants;
use B8im\ImShared\Support\RuntimeEnvironment;
use B8im\ImShared\Support\SingleConversationIdentity;
use B8im\ImShared\Telemetry\TraceContext;

require dirname(__DIR__) . '/vendor/autoload.php';

$packet = Packet::decode('{"cmd":"send","organization":999,"tenant_id":888,"data":{}}');
if (!$packet instanceof Packet || $packet->organization !== 999) {
    fwrite(STDERR, "[FAIL] canonical organization decode failed.\n");
    exit(1);
}

$traceparent = '00-4bf92f3577b34da6a3ce929d0e0e4736-00f067aa0ba902b7-01';
$traced = Packet::decode(json_encode([
    'cmd' => 'send',
    'organization' => 999,
    'data' => [],
    'traceparent' => $traceparent,
    'tracestate' => 'vendor=value',
], JSON_THROW_ON_ERROR));
if (!$traced instanceof Packet
    || $traced->traceContext()?->traceId() !== '4bf92f3577b34da6a3ce929d0e0e4736'
    || !str_contains($traced->encode(), 'traceparent')) {
    fwrite(STDERR, "[FAIL] W3C trace context packet propagation failed.\n");
    exit(1);
}
$untraced = Packet::make('ping');
if (str_contains($untraced->encode(), 'traceparent') || str_contains($untraced->encode(), 'tracestate')) {
    fwrite(STDERR, "[FAIL] absent trace context was not omitted.\n");
    exit(1);
}
foreach ([
    '00-' . str_repeat('0', 32) . '-00f067aa0ba902b7-01',
    '00-4bf92f3577b34da6a3ce929d0e0e4736-' . str_repeat('0', 16) . '-01',
    'ff-4bf92f3577b34da6a3ce929d0e0e4736-00f067aa0ba902b7-01',
    strtoupper($traceparent),
] as $invalidTraceparent) {
    $businessPacket = Packet::decode(json_encode([
        'cmd' => 'send',
        'client_msg_id' => 'valid-business-command',
        'traceparent' => $invalidTraceparent,
    ], JSON_THROW_ON_ERROR));
    if (!$businessPacket instanceof Packet
        || $businessPacket->clientMsgId !== 'valid-business-command'
        || $businessPacket->traceContext() !== null) {
        fwrite(STDERR, "[FAIL] invalid traceparent did not fall back to an untraced business packet.\n");
        exit(1);
    }
}
try {
    new TraceContext($traceparent, 'vendor=value,vendor=duplicate');
    fwrite(STDERR, "[FAIL] duplicate tracestate key was accepted.\n");
    exit(1);
} catch (InvalidArgumentException) {
}

$trusted = $packet->withServerOrganization(7);
if ($trusted->organization !== 7 || $packet->organization !== 999) {
    fwrite(STDERR, "[FAIL] server organization replacement failed.\n");
    exit(1);
}

$legacyOnly = Packet::decode('{"cmd":"send","tenant_id":888,"data":{}}');
if (!$legacyOnly instanceof Packet || $legacyOnly->organization !== 0) {
    fwrite(STDERR, "[FAIL] tenant_id was accepted as organization.\n");
    exit(1);
}

if (MessageType::isClientSendable(MessageType::SYSTEM)
    || !MessageType::isClientSendable(MessageType::TEXT)
    || !MessageType::isFirstStage(MessageType::SYSTEM)) {
    fwrite(STDERR, "[FAIL] client/system message type boundary failed.\n");
    exit(1);
}

if (
    Command::CONVERSATION_ACCESS_CHANGED !== 'conversation.access_changed'
    || Constants::MQ_ROUTING_CONVERSATION_ACCESS_CHANGED !== Command::CONVERSATION_ACCESS_CHANGED
    || Constants::MQ_ROUTING_MESSAGE_RECEIPT !== 'message.receipt'
    || Constants::MQ_ROUTING_CONVERSATION_READ !== 'conversation.read'
    || sprintf(Constants::REDIS_REALTIME_RETRY, str_repeat('a', 64))
        !== 'im:realtime:retry:event:' . str_repeat('a', 64)
) {
    fwrite(STDERR, "[FAIL] realtime routing and stable retry contracts diverged.\n");
    exit(1);
}

if (
    Command::GROUP_MEMBER_ACCESS_SNAPSHOT !== 'group_member_access_snapshot'
    || Command::GROUP_MEMBER_ACCESS_SNAPSHOT_ACK !== 'group_member_access_snapshot_ack'
    || Command::GROUP_MEMBER_ACCESS_CHANGED !== 'group_member_access_changed'
    || Constants::MQ_ROUTING_GROUP_MEMBER_ACCESS_CHANGED !== 'group.member_access_changed'
) {
    fwrite(STDERR, "[FAIL] group member access commands diverged.\n");
    exit(1);
}

$activeGroupAccess = new GroupMemberAccessEntry(
    'group:7:alpha',
    2,
    '2',
    GroupMemberAccessEntry::ACTIVE,
    '19',
    '4',
    [
        new GroupMemberAccessPeriod('1', '1', '9'),
        new GroupMemberAccessPeriod('2', '15', null),
    ],
);
if ($activeGroupAccess->toArray()['periods'][1]['to_seq'] !== null) {
    fwrite(STDERR, "[FAIL] group member access period DTO changed its wire contract.\n");
    exit(1);
}
$firstAccessRequest = GroupMemberAccessSnapshotRequest::fromArray([
    'access_snapshot_id' => null,
    'cursor' => null,
    'limit' => 50,
]);
$nextAccessRequest = GroupMemberAccessSnapshotRequest::fromArray([
    'access_snapshot_id' => '7',
    'cursor' => 'opaque.cursor',
    'limit' => 50,
]);
if ($firstAccessRequest->accessSnapshotId !== null || $nextAccessRequest->accessSnapshotId !== '7') {
    fwrite(STDERR, "[FAIL] group access snapshot request DTO changed its wire contract.\n");
    exit(1);
}
$terminalAccessPage = new GroupMemberAccessSnapshotPage('7', [$activeGroupAccess], null, false);
if ($terminalAccessPage->toArray()['has_more'] !== false) {
    fwrite(STDERR, "[FAIL] group access snapshot page DTO changed its wire contract.\n");
    exit(1);
}
$changedAccess = new GroupMemberAccessChanged(
    7,
    'member-1',
    '7',
    $activeGroupAccess,
    GroupMemberAccessChanged::REASON_JOIN,
    '2026-07-20 19:30:00',
);
if ($changedAccess->toArray()['reason'] !== 'join') {
    fwrite(STDERR, "[FAIL] group access reason changed its wire contract.\n");
    exit(1);
}
try {
    new GroupMemberAccessChanged(7, 'member-1', '7', $activeGroupAccess, 'unknown', '2026-07-20 19:30:00');
    fwrite(STDERR, "[FAIL] unknown group access reason was accepted.\n");
    exit(1);
} catch (InvalidArgumentException) {
}
foreach ([
    ['access_snapshot_id' => null, 'cursor' => 'cursor', 'limit' => 50],
    ['access_snapshot_id' => '7', 'cursor' => null, 'limit' => 50],
    ['access_snapshot_id' => 7, 'cursor' => 'cursor', 'limit' => 50],
    ['access_snapshot_id' => null, 'cursor' => null, 'limit' => 0],
    ['access_snapshot_id' => null, 'cursor' => null, 'limit' => '50'],
] as $invalidRequest) {
    try {
        GroupMemberAccessSnapshotRequest::fromArray($invalidRequest);
        fwrite(STDERR, "[FAIL] invalid group access snapshot request was accepted.\n");
        exit(1);
    } catch (InvalidArgumentException) {
    }
}
foreach ([
    static fn () => new GroupMemberAccessSnapshotPage('7', [], 'cursor', true),
    static fn () => new GroupMemberAccessSnapshotPage('7', [$activeGroupAccess], null, true),
    static fn () => new GroupMemberAccessSnapshotPage('7', [$activeGroupAccess], 'cursor', false),
    static fn () => new GroupMemberAccessSnapshotPage('7', [$activeGroupAccess], "bad\0cursor", true),
    static fn () => new GroupMemberAccessSnapshotPage('7', [1 => $activeGroupAccess], null, false),
    static fn () => new GroupMemberAccessSnapshotPage('7', [$activeGroupAccess, $activeGroupAccess], null, false),
] as $invalidPage) {
    try {
        $invalidPage();
        fwrite(STDERR, "[FAIL] invalid group access snapshot page was accepted.\n");
        exit(1);
    } catch (InvalidArgumentException) {
    }
}
foreach ([
    static fn () => new GroupMemberAccessPeriod('01', '1', null),
    static fn () => new GroupMemberAccessPeriod('1', CanonicalDecimal::UNSIGNED_BIGINT_MAX . '0', null),
    static fn () => new GroupMemberAccessPeriod('1', '9', '8'),
    static fn () => new GroupMemberAccessChanged(7, 'member|1', '7', $activeGroupAccess, 'join', '2026-07-20 19:30:00'),
    static fn () => new GroupMemberAccessEntry(' group:7:alpha', 2, '1', 'history_only', '0', '0', [
        new GroupMemberAccessPeriod('1', '1', null),
    ]),
    static fn () => new GroupMemberAccessEntry('group|7:alpha', 2, '1', 'revoked', '0', '0', []),
    static fn () => new GroupMemberAccessEntry('group:7:alpha', 1, '1', 'revoked', '0', '0', []),
    static fn () => new GroupMemberAccessEntry('group:7:alpha', 2, '1', 'revoked', '0', '0', [
        new GroupMemberAccessPeriod('1', '1', '2'),
    ]),
    static fn () => new GroupMemberAccessEntry('group:7:alpha', 2, '1', 'active', '0', '0', [
        1 => new GroupMemberAccessPeriod('1', '1', null),
    ]),
    static fn () => new GroupMemberAccessEntry('group:7:alpha', 2, '1', 'active', '0', '0', [
        new GroupMemberAccessPeriod('2', '5', '9'),
        new GroupMemberAccessPeriod('2', '10', null),
    ]),
    static fn () => new GroupMemberAccessEntry('group:7:alpha', 2, '1', 'active', '0', '0', [
        new GroupMemberAccessPeriod('1', '1', null),
        new GroupMemberAccessPeriod('2', '10', '12'),
    ]),
] as $invalidAccessDto) {
    try {
        $invalidAccessDto();
        fwrite(STDERR, "[FAIL] invalid group member access DTO was accepted.\n");
        exit(1);
    } catch (InvalidArgumentException) {
    }
}

$singleConversationVectors = [
    [[1, 'u1', 2, 'u2'], 'single_2118193dd11825a86050c3575d1f9aa52849d5e3'],
    [[1, 'same', 2, 'same'], 'single_3d9ff05c919aa120bba0770a87bf422ba31e2e8b'],
    [[7, 'alice', 7, 'bob'], 'single_06077c21d48263b3d726c0c3df9daadb63e2a9b7'],
];
foreach ($singleConversationVectors as [$input, $expected]) {
    if (SingleConversationIdentity::conversationId(...$input) !== $expected) {
        fwrite(STDERR, "[FAIL] single conversation identity vector diverged.\n");
        exit(1);
    }
}

$originalTimezone = date_default_timezone_get();
if (RuntimeEnvironment::configureTimezone('Asia/Shanghai') !== 'Asia/Shanghai'
    || date_default_timezone_get() !== 'Asia/Shanghai') {
    fwrite(STDERR, "[FAIL] canonical IM timezone was not applied.\n");
    exit(1);
}
date_default_timezone_set($originalTimezone);

$strongSecret = str_repeat('s', 32);
if (RuntimeEnvironment::requireInternalSecret($strongSecret) !== $strongSecret) {
    fwrite(STDERR, "[FAIL] strong internal secret was rejected.\n");
    exit(1);
}

$_ENV['B8IM_RUNTIME_ENV_TEST'] = 'dotenv-value';
putenv('B8IM_RUNTIME_ENV_TEST=process-value');
if (RuntimeEnvironment::value('B8IM_RUNTIME_ENV_TEST') !== 'process-value') {
    fwrite(STDERR, "[FAIL] process environment did not override dotenv state.\n");
    exit(1);
}
putenv('B8IM_RUNTIME_ENV_TEST');
unset($_ENV['B8IM_RUNTIME_ENV_TEST']);
foreach (['', 'short-secret', 'please-change-me-to-a-strong-random-key', str_repeat('x', 31)] as $invalidSecret) {
    try {
        RuntimeEnvironment::requireInternalSecret($invalidSecret);
        fwrite(STDERR, "[FAIL] weak or placeholder internal secret was accepted.\n");
        exit(1);
    } catch (InvalidArgumentException) {
    }
}

foreach (['example-secret-that-is-long-enough-to-pass', 'replace-with-a-strong-random-secret-now', 'placeholder_secret_that_is_long_enough'] as $invalidSecret) {
    try {
        RuntimeEnvironment::requireSecret($invalidSecret, 'IM_TOKEN_SECRET');
        fwrite(STDERR, "[FAIL] generic placeholder secret was accepted.\n");
        exit(1);
    } catch (InvalidArgumentException) {
    }
}

fwrite(STDOUT, "[PASS] packet boundaries, timezone and internal transport secret are canonical\n");
