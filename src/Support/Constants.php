<?php
// +----------------------------------------------------------------------
// | b8im [ 即时通讯系统 ]
// +----------------------------------------------------------------------
// | IM 通信层公共包 - 全局常量
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace B8im\ImShared\Support;

/**
 * 全局常量
 *
 * Redis key 必须带租户前缀（readme §5.1）。这里集中约定 key 模板，
 * 三件套统一引用，避免散落字符串。
 */
final class Constants
{
    /** 在线状态：im:{organization}:online:{user_id} -> client_id 列表 */
    public const REDIS_ONLINE = 'im:%d:online:%s';

    /** 全局连接索引：im:client:{client_id} -> organization + session_id JSON */
    public const REDIS_CLIENT_INDEX = 'im:client:%s';

    /** 连接映射：im:{organization}:client:{client_id} -> 完整鉴权会话 JSON */
    public const REDIS_CLIENT = 'im:%d:client:%s';

    /** 限流：im:{organization}:rate:{user_id} */
    public const REDIS_RATE = 'im:%d:rate:%s';

    /** 设备多连接：im:{organization}:devices:{user_id} -> connection session_id => 会话 JSON */
    public const REDIS_DEVICES = 'im:%d:devices:%s';

    /** 已验证凭证会话短缓存：im:auth:active:{organization}:{credential_session_id} */
    public const REDIS_AUTH_ACTIVE = 'im:auth:active:%d:%s';

    /** 已停用机构即时阻断标记：每条已鉴权命令先于正缓存检查 */
    public const REDIS_AUTH_ORGANIZATION_INACTIVE = 'im:auth:organization_inactive:%d';

    /** 控制面实时事件队列：server -> im-business */
    public const REDIS_REALTIME_EVENTS = 'im:events:realtime';

    /** 控制面实时事件已领取原文：claim_token -> raw envelope */
    public const REDIS_REALTIME_EVENT_PROCESSING = 'im:events:realtime:processing';

    /** 控制面实时事件已领取 ID：claim_token -> event_id */
    public const REDIS_REALTIME_EVENT_PROCESSING_IDS = 'im:events:realtime:processing_ids';

    /** 控制面实时事件已领取 worker：claim_token -> worker_id */
    public const REDIS_REALTIME_EVENT_PROCESSING_WORKERS = 'im:events:realtime:processing_workers';

    /** 控制面实时事件在途唯一索引：event_id -> claim_token */
    public const REDIS_REALTIME_EVENT_INFLIGHT = 'im:events:realtime:inflight';

    /** 控制面实时事件 claim 租约：claim_token -> expires_at_ms */
    public const REDIS_REALTIME_EVENT_LEASES = 'im:events:realtime:leases';

    /** 控制面实时事件去重集：event_id -> expires_at_ms */
    public const REDIS_REALTIME_EVENT_DONE = 'im:events:realtime:done';

    /** RabbitMQ 实时补投重试：稳定事件幂等 ID */
    public const REDIS_REALTIME_RETRY = 'im:realtime:retry:event:%s';

    /** 模块运行时启用缓存：与 Server 共用 JSON snapshot，后台变更后删除 */
    public const REDIS_MODULE_LICENSE = 'module_license:%d:%s';

    /** RabbitMQ 消息落库事件 routing key */
    public const MQ_ROUTING_MESSAGE_CREATED = 'message.created';

    /** RabbitMQ 消息撤回事件 routing key */
    public const MQ_ROUTING_MESSAGE_RECALLED = 'message.recalled';

    /** RabbitMQ 消息编辑事件 routing key */
    public const MQ_ROUTING_MESSAGE_EDITED = 'message.edited';

    /** RabbitMQ 消息双向删除事件 routing key */
    public const MQ_ROUTING_MESSAGE_DELETED_BOTH = 'message.deleted_both';

    /** RabbitMQ 消息单向删除事件 routing key */
    public const MQ_ROUTING_MESSAGE_DELETED_SELF = 'message.deleted_self';

    /** RabbitMQ 消息送达/已读回执 routing key */
    public const MQ_ROUTING_MESSAGE_RECEIPT = 'message.receipt';

    /** RabbitMQ 会话已读游标 routing key */
    public const MQ_ROUTING_CONVERSATION_READ = 'conversation.read';

    /** RabbitMQ 跨机构会话访问快照变更 routing key */
    public const MQ_ROUTING_CONVERSATION_ACCESS_CHANGED = 'conversation.access_changed';

    /** RabbitMQ 群成员历史访问变更 routing key */
    public const MQ_ROUTING_GROUP_MEMBER_ACCESS_CHANGED = 'group.member_access_changed';

    /** RabbitMQ 群消息分发 routing key */
    public const MQ_ROUTING_GROUP_FANOUT = 'message.group.fanout';

    /** RabbitMQ 离线推送 routing key */
    public const MQ_ROUTING_OFFLINE_PUSH = 'message.offline.push';

    /** RabbitMQ 审计 routing key */
    public const MQ_ROUTING_MESSAGE_AUDIT = 'message.audit';

    /** RabbitMQ 消息后处理队列（readme §6.8 异步任务）*/
    public const MQ_MESSAGE_AFTER = 'im.message.after';

    /** RabbitMQ 群消息 fanout 队列 */
    public const MQ_GROUP_FANOUT = 'im.group.fanout';

    /** RabbitMQ 离线推送队列 */
    public const MQ_OFFLINE_PUSH = 'im.push.offline';

    /** RabbitMQ 审计队列 */
    public const MQ_MESSAGE_AUDIT = 'im.audit.message';

    /** RabbitMQ 死信队列 */
    public const MQ_MESSAGE_DLX = 'im.message.dlx';

    /** 默认心跳间隔（秒），客户端超过此值未发 ping 视为掉线 */
    public const HEARTBEAT_INTERVAL = 55;
}
