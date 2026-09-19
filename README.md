# Captcha

Pin 验证码，提供统一的验证码生成、校验和接口支持，内置倒序验证等多种验证规则。

## 使用

需要支持 FreeType 的 GD 扩展；默认使用 Redis 保存一次性验证码。

```php
use Pin\Captcha\Captcha;

$captcha = Captcha::generate();
// 对客户端返回 token、data、width、height，text 仅供服务端使用。

$result = Captcha::verify($input.'|'.$token);
if ($result->err !== null) {
    // 验证失败，err 为 Pin\Captcha\Errors 中的错误码。
}

// 验证失败时抛出 CaptchaException：
Captcha::validate($input.'|'.$token);
```

内置路由 `GET /api/captcha` 返回透明 PNG 的 Data URL 与 Token，支持 `?theme=dark`。
`GET /api/captcha/rules` 返回规则列表，需要登录。
`routes.generate.extras.enabled` 仅作为响应字段，不控制验证码生成；关闭内置路由请设置 `routes.enabled`。

## 配置与规则

配置位于 `config/pin/captcha.php`，通过 `pin-captcha-config` 标签发布。
`config` 支持 `rule`、`width`、`height`、`angle`、`font`、`font_size`、`expires`；
图片尺寸、字号和有效期必须大于 0，角度不能为负数，字体必须是可读的 TTF 文件。
未知配置项会抛出异常，便于发现拼写错误。

| 规则 | 参数 | 验证码为 `6809` 时的输入 |
| --- | --- | --- |
| `normal` | 无 | `6809` |
| `rev` | 无 | `9086` |
| `first:2` | 1–3 | `68` |
| `last:2` | 1–3 | `09` |
| `prepend:2` | 1–4 | `86809` |
| `append:2` | 1–4 | `68098` |
| `order:2134` | 由 1–4 组成的 1–5 位字符串，可重复 | `8609` |
| `fixed:abcd` | 非空字母或数字字符串 | `abcd` |

生成时，规则优先级为显式参数、`config.rule`、`normal`；显式传入空字符串使用 `normal`。
校验时，非空显式规则覆盖 Token 中的规则，空规则回退到 Token 规则，再回退到 `normal`。
普通验证码校验忽略大小写；`plain:a|a` 沿用 Pin 的非生产环境/API 文档调试机制，区分大小写。

## 缓存与扩展

- `cache_enabled` 默认开启。解码时先读取缓存，再删除缓存，输入错误也会消费验证码；缓存已删除时再次提交返回 `CaptchaMissing`，Token 过期返回 `CaptchaExpired`。
- 关闭缓存后，验证码在有效期内允许重复校验。
- `redis_connection` 指定 Redis 连接，默认 `default`。
- `CaptchaGenerator`、`CaptchaValidator`、`CaptchaToken` 和 `Config` 均可通过容器注入或替换，原有 `pin.captcha.*` 服务名仍可使用。
- `CaptchaToken::encode()` 的有效期必须大于 0。`VerifyRes` 保留原有名称与 `err` 字段，尚未解析的上下文字段为 `null`。

## 验证

在包目录安装依赖、配置 Redis 测试连接后运行：

```sh
vendor/bin/pest
vendor/bin/phpstan analyse
vendor/bin/pint --test
```

## 文档

[https://ipiner.cn/packages/captcha](https://ipiner.cn/packages/captcha)
