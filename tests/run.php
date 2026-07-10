<?php

declare(strict_types=1);

use B8im\ImShared\Protocol\Packet;
use B8im\ImShared\Protocol\MessageType;
use B8im\ImShared\Support\RuntimeEnvironment;

require dirname(__DIR__) . '/vendor/autoload.php';

$packet = Packet::decode('{"cmd":"send","organization":999,"tenant_id":888,"data":{}}');
if (!$packet instanceof Packet || $packet->organization !== 999) {
    fwrite(STDERR, "[FAIL] canonical organization decode failed.\n");
    exit(1);
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
