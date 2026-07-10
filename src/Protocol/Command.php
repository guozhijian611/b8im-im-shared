<?php
// +----------------------------------------------------------------------
// | b8im [ 即时通讯系统 ]
// +----------------------------------------------------------------------
// | IM 通信层公共包 - 信令/指令类型常量
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace B8im\ImShared\Protocol;

/**
 * 客户端 <-> 服务端 信令指令
 *
 * WebSocket 帧统一用 JSON，结构见 Packet。cmd 字段取这里的值。
 * 与具体业务消息（MessageType）区分：这里是连接层/控制层指令。
 */
final class Command
{
    // —— 连接与鉴权（readme §6.2）——
    public const AUTH = 'auth';             // 客户端用 IM token 鉴权
    public const AUTH_ACK = 'auth_ack';     // 服务端鉴权结果
    public const PING = 'ping';             // 心跳
    public const PONG = 'pong';             // 心跳响应
    public const KICK = 'kick';             // 服务端踢下线（单点登录等）

    // —— 消息收发（readme §6.8）——
    public const SEND = 'send';             // 客户端发送消息
    public const SEND_ACK = 'send_ack';     // 服务端写库成功后的发送确认
    public const PUSH = 'push';             // 服务端推送消息
    public const ACK = 'ack';               // 客户端回执（已送达/已读）
    public const ACK_ACK = 'ack_ack';       // 服务端回执确认
    public const RECALL = 'recall';         // 撤回
    public const RECALL_ACK = 'recall_ack'; // 服务端撤回确认
    public const EDIT = 'edit';             // 编辑消息
    public const EDIT_ACK = 'edit_ack';     // 服务端编辑确认
    public const DELETE = 'delete';         // 删除消息
    public const DELETE_ACK = 'delete_ack'; // 服务端删除确认
    public const SCREENSHOT = 'screenshot'; // 截屏提示
    public const SCREENSHOT_ACK = 'screenshot_ack'; // 服务端截屏提示确认
    public const SYNC = 'sync';             // 离线/历史消息同步
    public const SYNC_ACK = 'sync_ack';     // 同步响应
    public const FRIEND_REQUEST = 'friend_request'; // 好友申请实时提醒

    // —— 在线状态与正在输入（readme §6.2 扩展）——
    public const TYPING = 'typing';                                 // 正在输入（客户端发，服务端中继给会话对方）
    public const PRESENCE = 'presence';                             // 查询一批用户的在线状态
    public const PRESENCE_ACK = 'presence_ack';                     // 在线状态查询响应

    // —— 会话读状态同步 ——
    public const CONVERSATION_READ = 'conversation_read';           // 会话级已读同步（多端 + 对方感知）
    public const CONVERSATION_READ_ACK = 'conversation_read_ack';   // 已读同步响应

    // —— 通用 ——
    public const ERROR = 'error';           // 错误响应
}
