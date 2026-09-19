<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Redis;
use Pin\Captcha\Captcha;
use Pin\Captcha\Errors;
use Pin\Support\Facades\Token;

it('decodes token', function ($cached) {
    config(['pin.captcha.cache_enabled' => $cached]);
    $data = Captcha::generate();

    expect($data['text'])->toBe(
        app('pin.captcha.token')->decode($data['token'])->text
    );
})->with([
    'cache enabled' => true,
    'cache_disabled' => false,
]);

it('preserves token metadata when consuming a cached captcha', function () {
    $encoded = app('pin.captcha.token')->encode('1234', 'normal', 60);
    $original = Token::decode($encoded);
    $decoded = app('pin.captcha.token')->decode($encoded);

    expect($decoded->jti)->toBe($original->jti)
        ->and($decoded->exp)->toBe($original->exp)
        ->and($decoded->raw)->toBe($encoded)
        ->and($decoded->text)->toBe('1234')
        ->and($decoded->rule)->toBe('normal')
        ->and(Captcha::verify('1234|'.$encoded)->err)->toBe(Errors::CaptchaMissing);
});

it('allows repeated verification when caching is disabled', function () {
    config(['pin.captcha.cache_enabled' => false]);
    $encoded = app('pin.captcha.token')->encode('1234', 'normal', 60);

    expect(Captcha::verify('1234|'.$encoded)->err)->toBeNull()
        ->and(Captcha::verify('1234|'.$encoded)->err)->toBeNull();
});

it('rejects expired captchas in both cache modes', function (bool $cached) {
    config(['pin.captcha.cache_enabled' => $cached]);
    $encoded = app('pin.captcha.token')->encode('1234', 'normal', 1);
    $this->travel(2)->seconds();

    expect(Captcha::verify('1234|'.$encoded)->err)->toBe(Errors::CaptchaExpired);
})->with([true, false]);

it('rejects invalid token payloads without caching', function (array $payload) {
    config(['pin.captcha.cache_enabled' => false]);
    $encoded = Token::encode($payload, 60);

    expect(Captcha::verify('1234|'.$encoded)->err)->toBe(Errors::CaptchaTokenInvalid);
})->with([
    'missing fields' => [[]],
    'missing rule' => [['text' => '1234']],
    'empty text' => [['text' => '', 'rule' => 'normal']],
    'array text' => [['text' => ['1234'], 'rule' => 'normal']],
    'array rule' => [['text' => '1234', 'rule' => ['normal']]],
]);

it('rejects cached tokens without a valid identifier', function (array $payload) {
    $encoded = Token::encode($payload, 60);

    expect(Captcha::verify('1234|'.$encoded)->err)->toBe(Errors::CaptchaTokenInvalid);
})->with([
    'missing identifier' => [[]],
    'empty identifier' => [['jti' => '']],
    'array identifier' => [['jti' => ['invalid']]],
]);

it('consumes corrupt cache entries and reports an invalid token', function (string $cached) {
    $tokenId = bin2hex(random_bytes(16));
    $encoded = Token::encode(['jti' => $tokenId], 60);
    Redis::connection()->setex('captcha:'.$tokenId, 60, $cached);

    expect(Captcha::verify('1234|'.$encoded)->err)->toBe(Errors::CaptchaTokenInvalid)
        ->and(Captcha::verify('1234|'.$encoded)->err)->toBe(Errors::CaptchaMissing);
})->with(['not-json', 'null', '[]', '{"text":"1234"}']);

it('uses the configured redis connection', function () {
    config([
        'database.redis.captcha_test' => config('database.redis.default'),
        'pin.captcha.redis_connection' => 'captcha_test',
    ]);

    $encoded = app('pin.captcha.token')->encode('1234', 'normal', 60);

    expect(Captcha::verify('1234|'.$encoded)->err)->toBeNull();
});

it('rejects nonpositive token lifetimes', function (int $ttl) {
    expect(fn () => app('pin.captcha.token')->encode('1234', 'normal', $ttl))
        ->toThrow(InvalidArgumentException::class);
})->with([0, -1]);
