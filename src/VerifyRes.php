<?php

declare(strict_types=1);

namespace Pin\Captcha;

use Pin\Errors\IError;
use Pin\Support\DataBag;
use Pin\Token\Token;

/**
 * 验证码验证结果
 *
 * @property IError|null $err 验证错误，`null` 表示验证通过
 * @property string $rule 验证规则
 * @property string $text 产生的验证码
 * @property string $input 用户输入的验证码
 * @property string $expectedInput 预期正确的验证码
 * @property ?Token $token
 *
 * @method static err(IError|null $err)
 */
class VerifyRes extends DataBag
{
    public function __construct()
    {
        parent::__construct(['err' => null]);
    }
}
