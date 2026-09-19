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
 * @property string|null $rule 验证规则
 * @property string|null $text 产生的验证码
 * @property string|null $input 用户输入的验证码
 * @property string|null $expectedInput 预期正确的验证码
 * @property ?Token $token
 */
class VerifyRes extends DataBag
{
    public function __construct()
    {
        parent::__construct([
            'err' => null,
            'rule' => null,
            'text' => null,
            'input' => null,
            'expectedInput' => null,
            'token' => null,
        ]);
    }

    public function err(?IError $err): static
    {
        $this->err = $err;

        return $this;
    }
}
