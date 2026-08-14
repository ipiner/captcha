<?php

declare(strict_types=1);

namespace Pin\Captcha;

use Pin\Http\Middleware\TransformsRequest;
use Pin\Token\Exceptions\TokenExpiredException;

/**
 * 验证码校验
 */
class CaptchaValidator
{
    /**
     * 验证
     *
     * 未通过会抛 `CaptchaException` 异常
     *
     * payload 格式：`input|encoded`
     *
     * 其中：
     * - input: 用户输入的验证码
     * - encoded: 服务端生成的 token（包含 text / rule / expire 等信息）
     *
     * @throws CaptchaException
     */
    public function validate(string $payload, ?string $rule = null): void
    {
        $res = $this->verify($payload, $rule);

        if ($res->err !== null) {
            throw new CaptchaException($res->err);
        }
    }

    /**
     * 执行校验
     */
    public function verify(string $payload, ?string $rule = null): VerifyRes
    {
        $res = new VerifyRes();

        // payload 基础格式校验
        if (! str_contains($payload, '|')) {
            return $res->err(Errors::CaptchaValueInvalid);
        }

        // 拆分 input 和 encoded token
        [$input, $encoded] = explode('|', $payload);
        $res->input = $input;

        if ($plain = TransformsRequest::resolvePlainValue($input)) {
            return $res->err($plain === $encoded ? null : Errors::CaptchaMismatch);
        }

        // 解码 token（包含 text / rule / expire）
        try {
            $token = app('pin.captcha.token')->decode($encoded);
            $res->token = $token;
        } catch (TokenExpiredException) {
            return $res->err(Errors::CaptchaExpired);
        } catch (CaptchaException) {
            return $res->err(Errors::CaptchaMissing);
        }

        /**
         * rule 优先级：
         * 1. 外部传入 rule
         * 2. token 内 rule
         */
        $rule = $rule ?: $token->rule;
        $res->rule = $rule;

        /**
         * 解析规则字符串：
         * 返回 [Rule, param]
         */
        [$rule, $param] = Rule::parse($rule);
        $res->text = $token->text;
        $res->expectedInput = $this->transform($rule, $param, $res->text);

        if ($this->check($res->expectedInput, $input)) {
            return $res;
        }

        return $res->err(Errors::CaptchaMismatch);
    }

    /**
     * 校验两个验证码是否匹配
     *
     * 比较忽略大小写
     *
     * @param  string  $expectedInput  期望正确的输入
     * @param  string  $input  用户输入
     */
    protected function check(string $expectedInput, string $input): bool
    {
        return strtoupper($expectedInput) === strtoupper($input);
    }

    /**
     * 核心转换逻辑
     *
     * - Normal   : 原值
     * - Rev      : 反转
     * - FirstN   : 截取前N位
     * - LastN    : 截取后N位
     * - PrependN : 拼接第N位字符 + 原值
     * - AppendN  : 原值 + 第N位字符
     * - Order    : 按 param 指定顺序重排
     * - Fixed    : 使用固定值 param
     */
    protected function transform(
        Rule $rule,
        ?string $param,
        string $text
    ): string {
        $n = (int) $param;

        return match ($rule) {
            Rule::Normal => $text,
            Rule::Rev => strrev($text),
            Rule::FirstN => substr($text, 0, $n),
            Rule::LastN => substr($text, -$n),

            // 前置拼接规则
            // actual = 6809, n=2 => "6" + "6809"
            Rule::PrependN => $text[$n - 1].$text,

            // 后置拼接规则
            // actual = 6809, n=2 => "6809" + "8"
            Rule::AppendN => $text.$text[$n - 1],

            /**
             * 顺序重排规则
             *
             * param 示例：
             * "2134"
             *
             * 表示：
             * - 第2位
             * - 第1位
             * - 第3位
             * - 第4位
             */
            Rule::Order => implode('', array_map(
                fn ($i) => $text[$i - 1] ?? '',
                str_split($param)
            )),

            // 固定值规则
            // 忽略 actual，直接使用 param
            Rule::Fixed => $param ?? '',
        };
    }
}
