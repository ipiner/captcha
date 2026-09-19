<?php

declare(strict_types=1);

use Pin\Captcha\CaptchaRuleException;
use Pin\Captcha\Rule;

it('parses valid captcha rules', function (
    string $input,
    Rule $expectedRule,
    ?string $expectedParam,
) {
    [$rule, $param] = Rule::parse($input);

    expect($rule)->toBe($expectedRule)
        ->and($param)->toBe($expectedParam);

})->with([
    'normal' => ['normal', Rule::Normal, null],
    'reverse' => ['rev', Rule::Rev, null],
    'order' => ['order:1234', Rule::Order, '1234'],
    'first n' => ['first:1', Rule::FirstN, '1'],
    'last n' => ['last:2', Rule::LastN, '2'],
    'prepend n' => ['prepend:2', Rule::PrependN, '2'],
    'append n' => ['append:2', Rule::AppendN, '2'],
    'fixed value' => ['fixed:1232', Rule::Fixed, '1232'],
]);

it('throws exception for invalid captcha rules', function (
    string $input,
    string $message,
) {
    try {
        Rule::parse($input);

        $this->fail('Expected CaptchaRuleException was not thrown.');
    } catch (CaptchaRuleException $e) {
        expect($e->getResponseMessage())->toContain($message);
    }

})->with([
    'missing order param' => [
        'order',
        '非法规则 "order"',
    ],

    'unknown rule' => [
        'unknown:123',
        '未知规则 "unknown"',
    ],

    'invalid order param' => [
        'order:632',
        '参数必须为1-5位的1234组合',
    ],

    'invalid fixed param' => [
        'fixed:123*',
        '只能由数字和字母组成',
    ],

    'invalid first param' => [
        'first:4',
        '参数必须为 1/2/3',
    ],

    'invalid last param' => [
        'last:0',
        '参数必须为 1/2/3',
    ],
    'normal with parameter' => ['normal:1', '非法规则'],
    'reverse with parameter' => ['rev:', '非法规则'],
    'empty fixed value' => ['fixed:', '只能由数字和字母组成'],
    'fixed with trailing newline' => ["fixed:abcd\n", '只能由数字和字母组成'],
    'order with trailing newline' => ["order:1234\n", '参数必须为1-5位的1234组合'],
    'too many order positions' => ['order:123412', '参数必须为1-5位的1234组合'],
    'invalid append position' => ['append:5', '参数必须为 1/2/3/4'],
]);

it('returns all captcha rules metadata', function () {
    $all = Rule::all();

    expect($all)->toBeArray()->not()->toBeEmpty();

    foreach ($all as $item) {
        expect($item)
            ->toHaveKey('rule')
            ->toHaveKey('label')
            ->toHaveKey('has_param');
    }
});

it('returns captcha rule label', function (Rule $rule, string $expected) {
    expect($rule->label())->toBe($expected);
})->with([
    'normal label' => [Rule::Normal, '正常验证'],
    'reverse label' => [Rule::Rev, '倒序验证'],
    'order label' => [Rule::Order, '按指定顺序验证'],
]);
