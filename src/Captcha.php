<?php

declare(strict_types=1);

namespace Pin\Captcha;

/**
 * 验证码处理
 */
class Captcha
{
    /**
     * 生成验证码
     *
     * @param  string|null  $rule  校验规则，{@see Rule}
     * @param  bool  $dark  是否暗黑模式
     * @return array{
     *     text: string,
     *     width: int,
     *     height: int,
     *     token: string,
     *     data: string
     * }
     */
    public static function generate(?string $rule = null, bool $dark = false): array
    {
        return app('pin.captcha.generator')->generate($rule, $dark);
    }

    /**
     * 验证码验证
     *
     * 未通过会抛 `CaptchaException` 异常
     *
     * @throws CaptchaException
     */
    public static function validate(string $payload, ?string $rule = null): void
    {
        app('pin.captcha.validator')->validate($payload, $rule);
    }

    /**
     * 验证码验证
     *
     * payload 格式：`input.encoded`
     *
     * 其中：
     * - input: 用户输入的验证码
     * - encoded: 服务端生成的 token（包含 text / rule / expire 等信息）
     */
    public static function verify(string $payload, ?string $rule = null): VerifyRes
    {
        return app('pin.captcha.validator')->verify($payload, $rule);
    }
}
