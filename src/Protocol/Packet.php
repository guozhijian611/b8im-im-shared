<?php
// +----------------------------------------------------------------------
// | b8im [ 即时通讯系统 ]
// +----------------------------------------------------------------------
// | IM 通信层公共包 - 统一 WebSocket 数据帧
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace B8im\ImShared\Protocol;

use B8im\ImShared\Telemetry\TraceContext;

/**
 * 统一数据帧
 *
 * 所有 WebSocket 帧统一 JSON 结构，方便三件套与客户端共识：
 *   {
 *     "cmd": "send",           // Command 常量
 *     "organization": 0,        // 机构隔离（readme §5.1，所有 MQ/连接都要带）
 *     "data": { ... },         // 业务负载
 *     "client_msg_id": "...",  // 客户端去重 ID（readme §6.7）
 *     "ts": 0                  // 毫秒时间戳
 *   }
 */
final class Packet
{
    public function __construct(
        public string $cmd,
        public array $data = [],
        public int $organization = 0,
        public ?string $clientMsgId = null,
        public int $ts = 0,
        public ?string $traceparent = null,
        public ?string $tracestate = null,
    ) {
        TraceContext::fromCarrier($this->traceparent, $this->tracestate);
    }

    /**
     * 从客户端原始字符串解析为 Packet。解析失败返回 null。
     */
    public static function decode(string $raw): ?self
    {
        $arr = json_decode($raw, true);
        if (!is_array($arr) || !isset($arr['cmd'])) {
            return null;
        }

        $traceparent = isset($arr['traceparent']) ? (string) $arr['traceparent'] : null;
        $tracestate = isset($arr['tracestate']) ? (string) $arr['tracestate'] : null;
        try {
            $trace = TraceContext::fromCarrier($traceparent, $tracestate);
            $traceparent = $trace?->traceparent;
            $tracestate = $trace?->tracestate;
        } catch (\InvalidArgumentException) {
            // Trace 是旁路诊断信号：非法 carrier 只丢弃上下文，不得将合法业务帧判为 PACKET_INVALID。
            $traceparent = null;
            $tracestate = null;
        }

        return new self(
            cmd: (string) $arr['cmd'],
            data: is_array($arr['data'] ?? null) ? $arr['data'] : [],
            organization: (int) ($arr['organization'] ?? 0),
            clientMsgId: isset($arr['client_msg_id']) ? (string) $arr['client_msg_id'] : null,
            ts: (int) ($arr['ts'] ?? 0),
            traceparent: $traceparent,
            tracestate: $tracestate,
        );
    }

    /**
     * 序列化为下发给客户端的字符串。
     */
    public function encode(): string
    {
        $packet = [
            'cmd' => $this->cmd,
            'organization' => $this->organization,
            'data' => $this->data,
            'client_msg_id' => $this->clientMsgId,
            'ts' => $this->ts ?: (int) (microtime(true) * 1000),
        ];
        if ($this->traceparent !== null) {
            $packet['traceparent'] = $this->traceparent;
        }
        if ($this->tracestate !== null) {
            $packet['tracestate'] = $this->tracestate;
        }

        return json_encode($packet, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /**
     * 快捷构造一个响应帧。
     */
    public static function make(
        string $cmd,
        array $data = [],
        int $organization = 0,
        ?string $clientMsgId = null,
        ?TraceContext $traceContext = null,
    ): self
    {
        return new self(
            cmd: $cmd,
            data: $data,
            organization: $organization,
            clientMsgId: $clientMsgId,
            traceparent: $traceContext?->traceparent,
            tracestate: $traceContext?->tracestate,
        );
    }

    /**
     * 以服务端鉴权上下文覆盖客户端帧中的 organization。
     *
     * 客户端 organization 只能用于故障诊断，不得进入模块授权、
     * 路由、持久化或下发响应。
     */
    public function withServerOrganization(int $organization): self
    {
        if ($organization <= 0) {
            throw new \InvalidArgumentException('server organization must be a positive integer');
        }

        return new self(
            cmd: $this->cmd,
            data: $this->data,
            organization: $organization,
            clientMsgId: $this->clientMsgId,
            ts: $this->ts,
            traceparent: $this->traceparent,
            tracestate: $this->tracestate,
        );
    }

    public function traceContext(): ?TraceContext
    {
        return TraceContext::fromCarrier($this->traceparent, $this->tracestate);
    }
}
