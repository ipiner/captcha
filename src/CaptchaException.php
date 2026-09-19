<?php

declare(strict_types=1);

namespace Pin\Captcha;

use Pin\Errors\IError;
use Pin\Exceptions\Exception;
use Throwable;

/**
 * 验证码校验异常
 */
class CaptchaException extends Exception
{
    public readonly IError $error;

    public function __construct(?IError $err = null, ?Throwable $previous = null)
    {
        $this->error = $err ?? Errors::CaptchaMismatch;

        parent::__construct($this->error, 0, $previous);
        $this->withStatusCode(422)->withResponseMessage(Errors::CaptchaMismatch->message());
    }
}
