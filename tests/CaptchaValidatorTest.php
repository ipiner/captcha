<?php

declare(strict_types=1);

namespace Pin\Tests\Captcha;

use Pin\Captcha\Captcha;
use Pin\Captcha\Errors;
use Pin\Captcha\Rule;
use Pin\Support\Facades\Token;

it('throws exception for invalid captcha value', function () {
    expect(fn () => Captcha::validate('s'))->toThrow(Errors::CaptchaValueInvalid->message());
});

it('verifies captcha payload', function ($payload, $rule, $expected) {
    $token = app('pin.captcha.token')->encode('1234', '', 300);
    expect(Captcha::verify(
        str_replace('%s', $token, $payload),
        is_string($rule) ? $rule : $rule->value
    ))
        ->err->toBe($expected);
})->with([
    'invalid payload' => [
        'payload' => 'x',
        'rule' => Rule::Normal,
        'expected' => Errors::CaptchaValueInvalid,
    ],

    'captcha missing' => fn () => [
        'payload' => 'value|'.Token::encode(['jti' => 's'], 60),
        'rule' => Rule::Normal,
        'expected' => Errors::CaptchaMissing,
    ],

    'expired captcha' => fn () => [
        'payload' => 'value|'.app('pin.captcha.token')->encode('code', '', -60),
        'rule' => Rule::Normal,
        'expected' => Errors::CaptchaExpired,
    ],

    'normal validation success' => [
        'payload' => '1234|%s',
        'rule' => Rule::Normal,
        'expected' => null,
    ],

    'reverse validation failed' => [
        'payload' => '1234|%s',
        'rule' => Rule::Rev,
        'expected' => Errors::CaptchaMismatch,
    ],

    'reverse validation success' => [
        'payload' => '4321|%s',
        'rule' => Rule::Rev,
        'expected' => null,
    ],

    'first n validation' => [
        'payload' => '12|%s',
        'rule' => 'first:2',
        'expected' => null,
    ],

    'last n validation' => [
        'payload' => '34|%s',
        'rule' => 'last:2',
        'expected' => null,
    ],

    'append validation' => [
        'payload' => '12341|%s',
        'rule' => 'append:1',
        'expected' => null,
    ],

    'prepend validation' => [
        'payload' => '11234|%s',
        'rule' => 'prepend:1',
        'expected' => null,
    ],

    'order validation' => [
        'payload' => '2211|%s',
        'rule' => 'order:2211',
        'expected' => null,
    ],

    'fixed validation success' => [
        'payload' => 'AbcD|%s',
        'rule' => 'fixed:abcd',
        'expected' => null,
    ],

    'fixed validation failed' => [
        'payload' => '1234|%s',
        'rule' => 'fixed:abcd',
        'expected' => Errors::CaptchaMismatch,
    ],
]);

it('verifies captcha for plain input', function (
    string $payload,
    ?Errors $expected,
) {
    expect(Captcha::verify($payload)->err)->toBe($expected);

})->with([
    'same value' => ['plain:a|a', null],
    'case mismatch' => ['plain:a|A', Errors::CaptchaMismatch],
]);
