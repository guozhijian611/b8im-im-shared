<?php
// +----------------------------------------------------------------------
// | b8im [ 即时通讯系统 ]
// +----------------------------------------------------------------------
// | IM 通信层公共包 - 模块 cmd 处理器接口
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace B8im\ImShared\Protocol;

/**
 * 模块 cmd 处理器接口
 *
 * 商业模块（客服、音视频等）通过实现此接口向 CmdDispatcher 注册自己的 cmd。
 * 核心 cmd（连接鉴权、消息收发）不走此接口，直接在 Events::onMessage 的 match 中处理。
 *
 * 注册示例（在模块 Bootstrapper 的 registerCmds 中）：
 *   $dispatcher->register(new MyCmdHandler(), fn(int $org) => $this->checkLicense($org));
 *
 * license guard 应在模块未启用时抛出 ImException('模块未启用', 'MODULE_NOT_LICENSED')。
 */
interface CmdHandlerInterface
{
    /**
     * 返回此 handler 处理的 cmd 字符串（与 Command 常量值对应）。
     */
    public function cmd(): string;

    /**
     * 处理 cmd。
     *
     * 调用前 CmdDispatcher 已执行 license guard，无需在此重复校验。
     * 需要推送响应时直接调用 Gateway 即可。
     *
     * @param string $clientId  GatewayWorker client_id
     * @param Packet $packet    已解码且 organization 已由服务端会话覆盖的请求帧
     */
    public function handle(string $clientId, Packet $packet): void;
}
