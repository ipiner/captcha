<?php

declare(strict_types=1);

use Pin\Captcha\Captcha;
use Pin\Captcha\Config;

it('generates captcha', function () {
    $data = Captcha::generate();

    expect($data['data'])->toContain('data:image/png;base64');
    expect($data['text'])->toBe(app('pin.captcha.token')->decode($data['token'])->text);
    expect(ctype_alnum($data['text']))->toBeTrue();
    expect(strlen($data['text']))->toBe(Config::LENGTH);
});

it('returns text color based on light/dark mode', function (bool $light, callable $check) {
    $o = $this->invoker(app('pin.captcha.generator'));
    $rgb = $o->getTextColor($light);
    expect($check($rgb))->toBeTrue();
})->with([
    'light' => [true, fn ($rgb) => $rgb[0] > 149 && $rgb[1] > 149 && $rgb[2] > 149],
    'dark' => [false, fn ($rgb) => $rgb[0] < 151 && $rgb[1] < 151 && $rgb[2] < 151],
]);
