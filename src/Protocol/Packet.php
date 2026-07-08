<?php
// +----------------------------------------------------------------------
// | b8im [ 即时通讯系统 ]
// +----------------------------------------------------------------------
// | IM 通信层公共包 - 统一 WebSocket 数据帧
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace B8im\ImShared\Protocol;

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
    ) {
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

        return new self(
            cmd: (string) $arr['cmd'],
            data: is_array($arr['data'] ?? null) ? $arr['data'] : [],
            organization: (int) ($arr['organization'] ?? $arr['tenant_id'] ?? 0),
            clientMsgId: isset($arr['client_msg_id']) ? (string) $arr['client_msg_id'] : null,
            ts: (int) ($arr['ts'] ?? 0),
        );
    }

    /**
     * 序列化为下发给客户端的字符串。
     */
    public function encode(): string
    {
        return json_encode([
            'cmd' => $this->cmd,
            'organization' => $this->organization,
            'data' => $this->data,
            'client_msg_id' => $this->clientMsgId,
            'ts' => $this->ts ?: (int) (microtime(true) * 1000),
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * 快捷构造一个响应帧。
     */
    public static function make(string $cmd, array $data = [], int $organization = 0, ?string $clientMsgId = null): self
    {
        return new self(cmd: $cmd, data: $data, organization: $organization, clientMsgId: $clientMsgId);
    }
}
