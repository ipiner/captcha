<?php

declare(strict_types=1);

namespace Pin\Captcha;

use Pin\Http\Middleware\TransformsRequest;
use Pin\Token\Exceptions\TokenException;
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
        $result = $this->verify($payload, $rule);

        if ($result->err !== null) {
            throw new CaptchaException($result->err);
        }
    }

    /**
     * 执行校验
     */
    public function verify(string $payload, ?string $rule = null): VerifyRes
    {
        $result = new VerifyRes();
        $parts = explode('|', $payload, 3);

        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            return $result->err(Errors::CaptchaValueInvalid);
        }

        [$input, $encoded] = $parts;
        $result->input = $input;

        $plain = TransformsRequest::resolvePlainValue($input);
        if ($plain !== null) {
            return $result->err($plain === $encoded ? null : Errors::CaptchaMismatch);
        }

        try {
            $token = app('pin.captcha.token')->decode($encoded);
        } catch (TokenExpiredException) {
            return $result->err(Errors::CaptchaExpired);
        } catch (TokenException) {
            return $result->err(Errors::CaptchaTokenInvalid);
        } catch (CaptchaException $exception) {
            return $result->err($exception->error);
        }

        $result->token = $token;
        $result->text = $token->text;
        // 外部规则优先；空规则沿用 Token 规则，最终回退到正常验证。
        $result->rule = $rule ?: ($token->rule ?: Rule::Normal->value);

        try {
            [$parsedRule, $parameter] = Rule::parse($result->rule);
            $result->expectedInput = $this->transform($parsedRule, $parameter, $result->text);
        } catch (CaptchaRuleException) {
            return $result->err(Errors::CaptchaRuleInvalid);
        }

        return $result->err($this->check($result->expectedInput, $input) ? null : Errors::CaptchaMismatch);
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
        return hash_equals(strtoupper($expectedInput), strtoupper($input));
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
        $position = (int) $param;
        $requiredLength = match ($rule) {
            Rule::FirstN, Rule::LastN, Rule::PrependN, Rule::AppendN => $position,
            Rule::Order => (int) max(str_split($param)),
            default => 0,
        };

        if ($requiredLength > strlen($text)) {
            throw new CaptchaRuleException('验证码长度不足以应用指定规则');
        }

        return match ($rule) {
            Rule::Normal => $text,
            Rule::Rev => strrev($text),
            Rule::FirstN => substr($text, 0, $position),
            Rule::LastN => substr($text, -$position),
            Rule::PrependN => $text[$position - 1].$text,
            Rule::AppendN => $text.$text[$position - 1],
            Rule::Order => implode('', array_map(
                static fn (string $index): string => $text[(int) $index - 1],
                str_split($param)
            )),
            Rule::Fixed => $param ?? '',
        };
    }
}
