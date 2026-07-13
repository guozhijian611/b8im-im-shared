<?php
// +----------------------------------------------------------------------
// | b8im [ 即时通讯系统 ]
// +----------------------------------------------------------------------
// | W3C Trace Context 跨端传播契约
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace B8im\ImShared\Telemetry;

final class TraceContext
{
    private const TRACEPARENT_PATTERN = '/^00-([0-9a-f]{32})-([0-9a-f]{16})-([0-9a-f]{2})$/D';

    public function __construct(
        public readonly string $traceparent,
        public readonly ?string $tracestate = null,
    ) {
        self::assertTraceparent($traceparent);
        self::assertTracestate($tracestate);
    }

    public static function fromCarrier(?string $traceparent, ?string $tracestate = null): ?self
    {
        if ($traceparent === null && $tracestate === null) {
            return null;
        }
        if ($traceparent === null || trim($traceparent) === '') {
            throw new \InvalidArgumentException('tracestate requires a valid traceparent');
        }

        return new self(trim($traceparent), self::normalizeTracestate($tracestate));
    }

    public function traceId(): string
    {
        return substr($this->traceparent, 3, 32);
    }

    public function spanId(): string
    {
        return substr($this->traceparent, 36, 16);
    }

    public function traceFlags(): int
    {
        return hexdec(substr($this->traceparent, 53, 2));
    }

    /** @return array{traceparent: string, tracestate?: string} */
    public function toCarrier(): array
    {
        $carrier = ['traceparent' => $this->traceparent];
        if ($this->tracestate !== null) {
            $carrier['tracestate'] = $this->tracestate;
        }

        return $carrier;
    }

    private static function assertTraceparent(string $traceparent): void
    {
        if (preg_match(self::TRACEPARENT_PATTERN, $traceparent, $matches) !== 1) {
            throw new \InvalidArgumentException('traceparent must be a canonical W3C version 00 value');
        }
        if ($matches[1] === str_repeat('0', 32) || $matches[2] === str_repeat('0', 16)) {
            throw new \InvalidArgumentException('traceparent trace-id and parent-id must be non-zero');
        }
    }

    private static function assertTracestate(?string $tracestate): void
    {
        if ($tracestate === null) {
            return;
        }
        if ($tracestate === '' || strlen($tracestate) > 512) {
            throw new \InvalidArgumentException('tracestate must contain 1..512 bytes');
        }

        $members = explode(',', $tracestate);
        if (count($members) > 32) {
            throw new \InvalidArgumentException('tracestate may contain at most 32 members');
        }

        $seen = [];
        foreach ($members as $member) {
            if ($member !== trim($member) || substr_count($member, '=') !== 1) {
                throw new \InvalidArgumentException('tracestate member is not canonical');
            }
            [$key, $value] = explode('=', $member, 2);
            $simpleKey = preg_match('/^[a-z][a-z0-9_\-*\/]{0,255}$/D', $key) === 1;
            $tenantKey = preg_match('/^[a-z0-9][a-z0-9_\-*\/]{0,240}@[a-z][a-z0-9_\-*\/]{0,13}$/D', $key) === 1;
            if ((!$simpleKey && !$tenantKey) || isset($seen[$key])) {
                throw new \InvalidArgumentException('tracestate key is invalid or duplicated');
            }
            if ($value === '' || strlen($value) > 256 || $value !== rtrim($value, ' ')
                || preg_match('/^[\x20-\x2b\x2d-\x3c\x3e-\x7e]+$/D', $value) !== 1) {
                throw new \InvalidArgumentException('tracestate value is invalid');
            }
            $seen[$key] = true;
        }
    }

    private static function normalizeTracestate(?string $tracestate): ?string
    {
        if ($tracestate === null) {
            return null;
        }
        $tracestate = trim($tracestate);

        return $tracestate === '' ? null : $tracestate;
    }
}
