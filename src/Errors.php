<?php

declare(strict_types=1);

namespace Pin\Captcha;

use Pin\Errors\Attribute\Group;
use Pin\Errors\Errorful;
use Pin\Errors\IError;

/**
 * 验证码错误码定义
 */
#[Group('pin-captcha::captcha')]
enum Errors: string implements IError
{
    use Errorful;

    case CaptchaMismatch = '2000|422|captcha_mismatch';
    case CaptchaMissing = '2001|422|captcha_missing';
    case CaptchaExpired = '2002|422|captcha_expired';
    case CaptchaValueInvalid = '2003|422|captcha_value_invalid';
    case CaptchaTokenInvalid = '2004|422|captcha_token_invalid';
    case CaptchaRuleInvalid = '2005|422|captcha_rule_invalid';
}
