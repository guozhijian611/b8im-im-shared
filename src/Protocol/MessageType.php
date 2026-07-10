<?php
// +----------------------------------------------------------------------
// | b8im [ 即时通讯系统 ]
// +----------------------------------------------------------------------
// | IM 通信层公共包 - 消息类型常量
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace B8im\ImShared\Protocol;

/**
 * 消息类型枚举
 *
 * 对应 readme §6.3 消息类型。第一阶段先定义文本/图片/文件/语音/视频/系统通知，
 * 其余为后续扩展预留。
 */
final class MessageType
{
    // —— 第一阶段 ——
    public const TEXT = 1;          // 文本消息
    public const IMAGE = 2;         // 图片消息
    public const FILE = 3;          // 文件消息
    public const VOICE = 4;         // 语音消息
    public const SYSTEM = 5;        // 系统通知消息
    public const VIDEO = 11;        // 视频消息

    // —— 后续扩展（预留，暂未启用）——
    public const LOCATION = 12;     // 位置消息
    public const CARD = 13;         // 名片消息
    public const CUSTOM = 14;       // 自定义业务消息
    public const RTC_SIGNAL = 15;   // 音视频通话信令
    public const LIVE = 16;         // 直播互动消息

    public static function allowedFirstStage(): array
    {
        return [
            self::TEXT,
            self::IMAGE,
            self::FILE,
            self::VOICE,
            self::SYSTEM,
            self::VIDEO,
        ];
    }

    public static function isFirstStage(int $type): bool
    {
        return in_array($type, self::allowedFirstStage(), true);
    }

    /** @return list<int> */
    public static function allowedClientSend(): array
    {
        return [self::TEXT, self::IMAGE, self::FILE, self::VOICE, self::VIDEO];
    }

    public static function isClientSendable(int $type): bool
    {
        return in_array($type, self::allowedClientSend(), true);
    }
}
