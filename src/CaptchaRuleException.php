<?php

declare(strict_types=1);

namespace Pin\Captcha;

use Throwable;

/**
 * 验证码规则配置异常
 */
class CaptchaRuleException extends CaptchaException
{
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct(Errors::CaptchaRuleInvalid, $previous);
        $this->withResponseMessage($message);
    }
}
