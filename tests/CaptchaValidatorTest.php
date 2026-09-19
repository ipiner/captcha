<?php

declare(strict_types=1);

namespace Pin\Tests\Captcha;

use Pin\Captcha\Captcha;
use Pin\Captcha\CaptchaException;
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
        'payload' => 'value|'.Token::encode(['text' => 'code', 'rule' => 'normal'], -60),
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
    'zero input' => ['plain:0|0', null],
]);

it('rejects malformed payloads without consuming the token', function (string $payload) {
    $token = app('pin.captcha.token')->encode('1234', 'normal', 60);

    expect(Captcha::verify(str_replace('%s', $token, $payload))->err)->toBe(Errors::CaptchaValueInvalid)
        ->and(Captcha::verify('1234|'.$token)->err)->toBeNull();
})->with(['', '|', '|%s', '1234|', '1234|%s|extra']);

it('returns a captcha error for invalid tokens', function () {
    expect(Captcha::verify('1234|not-a-token')->err)->toBe(Errors::CaptchaTokenInvalid);
});

it('uses the token rule or the normal fallback', function (string $storedRule, ?string $override, string $input) {
    $token = app('pin.captcha.token')->encode('1234', $storedRule, 60);

    expect(Captcha::verify($input.'|'.$token, $override)->err)->toBeNull();
})->with([
    ['rev', null, '4321'],
    ['rev', '', '4321'],
    ['rev', 'normal', '1234'],
    ['', null, '1234'],
]);

it('returns rule errors without leaking parser exceptions', function (string $rule) {
    $token = app('pin.captcha.token')->encode('1234', 'normal', 60);

    expect(Captcha::verify('1234|'.$token, $rule)->err)->toBe(Errors::CaptchaRuleInvalid);
})->with(['unknown', 'normal:1', 'first:0', "order:1234\n"]);

it('rejects rules that reference missing characters', function (string $rule) {
    $token = app('pin.captcha.token')->encode('12', 'normal', 60);

    expect(Captcha::verify('12|'.$token, $rule)->err)->toBe(Errors::CaptchaRuleInvalid);
})->with(['append:4', 'prepend:4', 'order:1234', 'first:3', 'last:3']);

it('consumes cached captchas even after an incorrect answer', function () {
    $token = app('pin.captcha.token')->encode('1234', 'normal', 60);

    expect(Captcha::verify('wrong|'.$token)->err)->toBe(Errors::CaptchaMismatch)
        ->and(Captcha::verify('1234|'.$token)->err)->toBe(Errors::CaptchaMissing);
});

it('returns a complete result for successful verification', function () {
    $encoded = app('pin.captcha.token')->encode('AbCd', 'rev', 60);
    $result = Captcha::verify('dCbA|'.$encoded);

    expect($result)->err->toBeNull()
        ->rule->toBe('rev')
        ->text->toBe('AbCd')
        ->input->toBe('dCbA')
        ->expectedInput->toBe('dCbA')
        ->and($result->token->raw)->toBe($encoded);
});

it('returns nullable context for rejected payloads', function () {
    $result = Captcha::verify('invalid');

    expect($result)->token->toBeNull()->text->toBeNull()->rule->toBeNull()->expectedInput->toBeNull();
});

it('preserves the verification error when validation throws', function () {
    try {
        Captcha::validate('1234|invalid');
        $this->fail('Expected a captcha exception.');
    } catch (CaptchaException $exception) {
        expect($exception->error)->toBe(Errors::CaptchaTokenInvalid)
            ->and($exception->getCode())->toBe(Errors::CaptchaTokenInvalid->code())
            ->and($exception->getResponseMessage())->toBe(Errors::CaptchaMismatch->message());
    }
});

it('does not allow plain input in production requests', function () {
    $this->app->instance('env', 'production');

    expect(Captcha::verify('plain:a|a')->err)->toBe(Errors::CaptchaTokenInvalid);
});
