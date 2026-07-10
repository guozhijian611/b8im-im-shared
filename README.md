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

三个服务在 `b8im-im/` 仓库内，通过 composer path repository 依赖本包：

```json
"repositories": [
    { "type": "path", "url": "../../b8im-im-shared" }
],
"require": {
    "b8im/im-shared": "*"
}
```

> 注意：Docker 打包时请在 `b8im-dev-workspace` 根目录构建，让镜像上下文同时包含
> `b8im-im/` 和 `b8im-im-shared/`；或后续把本包发布到私有 Composer 仓库。
