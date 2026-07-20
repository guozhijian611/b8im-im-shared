# im-shared

b8im IM 通信层公共包，供 `im-gateway` / `im-register` / `im-business` 三个独立服务复用。

## 内容

- `src/Protocol/MessageType.php` —— 消息类型常量（readme §6.3）
- `src/Protocol/Command.php` —— 信令/指令常量（鉴权、心跳、收发、回执）
- `src/Protocol/Packet.php` —— 统一 WebSocket JSON 数据帧编解码
- `src/Support/Constants.php` —— Redis key 模板（带机构前缀）、MQ routing key 和队列名等

## 数据帧

统一使用 `organization` 表示机构/租户隔离：

```json
{
  "cmd": "send",
  "organization": 1,
  "client_msg_id": "client-uuid",
  "data": {},
  "ts": 1782540000000
}
```

`Packet::decode()` 只读取 `organization`。开发版不保留 `tenant_id` 等旧字段。

## 引用方式

`SearchProjectionEvent` 与 canonical uint64 的 `CanonicalDecimal` 从发布标签
`v1.1.0` 开始提供。使用搜索投影协议的消费者必须声明 `b8im/im-shared:^1.1`，不能把仅含
旧协议的 `1.0.0` 当作兼容实现。

三个服务在 `b8im-im/` 仓库内，通过 Composer path repository 依赖本包；约束应按
实际使用的最低 API 版本声明，例如 `im-business` 使用搜索投影 DTO：

```json
"repositories": [
    { "type": "path", "url": "../../b8im-im-shared" }
],
"require": {
    "b8im/im-shared": "^1.1"
}
```

> 注意：Docker 打包时请在 `b8im-dev-workspace` 根目录构建，让镜像上下文同时包含
> `b8im-im/` 和 `b8im-im-shared/`。本地 path 只用于联调，正式交付必须先发布
> 对应版本的 private Composer 包或 `v1.1.0` release tag；不能通过 path version
> 映射冒充未发布版本。根包不在 `composer.json` 硬编码版本，Composer 版本以 tag 为准。
