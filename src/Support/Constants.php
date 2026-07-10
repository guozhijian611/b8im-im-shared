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

    /** 全局连接索引：im:client:{client_id} -> organization */
    public const REDIS_CLIENT_INDEX = 'im:client:%s';

    /** 连接映射：im:{organization}:client:{client_id} -> user_id/device_id */
    public const REDIS_CLIENT = 'im:%d:client:%s';

    /** 限流：im:{organization}:rate:{user_id} */
    public const REDIS_RATE = 'im:%d:rate:%s';

    /** 设备多端：im:{organization}:devices:{user_id} */
    public const REDIS_DEVICES = 'im:%d:devices:%s';

    /** 控制面实时事件队列：server -> im-business */
    public const REDIS_REALTIME_EVENTS = 'im:events:realtime';

    /** 模块运行时启用缓存：im:module:license:{organization}:{module_key} -> 1/0，后台变更时主动刷新 */
    public const REDIS_MODULE_LICENSE = 'im:module:license:%d:%s';

    /** RabbitMQ 消息落库事件 routing key */
    public const MQ_ROUTING_MESSAGE_CREATED = 'message.created';

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
