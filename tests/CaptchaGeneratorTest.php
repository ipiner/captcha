<?php

declare(strict_types=1);

use Pin\Captcha\Captcha;
use Pin\Captcha\CaptchaGenerator;
use Pin\Captcha\CaptchaRuleException;
use Pin\Captcha\Config;

it('generates captcha', function () {
    $data = Captcha::generate();

    expect($data['data'])->toContain('data:image/png;base64');
    expect($data['text'])->toBe(app('pin.captcha.token')->decode($data['token'])->text);
    expect(ctype_alnum($data['text']))->toBeTrue();
    expect(strlen($data['text']))->toBe(Config::LENGTH);
});

it('returns text color based on light/dark mode', function (bool $dark, callable $check) {
    $generator = $this->invoker(app('pin.captcha.generator'));
    $rgb = $generator->getTextColor($dark);
    expect($check($rgb))->toBeTrue();
})->with([
    'dark theme' => [true, fn ($rgb) => $rgb[0] > 149 && $rgb[1] > 149 && $rgb[2] > 149],
    'light theme' => [false, fn ($rgb) => $rgb[0] < 151 && $rgb[1] < 151 && $rgb[2] < 151],
]);

it('renders transparent PNGs with all bundled fonts and large font sizes', function (int $font, int $fontSize) {
    config(['pin.captcha.cache_enabled' => false]);
    $config = new Config(['font' => dirname(__DIR__).'/src/fonts/'.$font.'.ttf', 'font_size' => $fontSize]);
    $generator = new CaptchaGenerator($config);
    $bufferLevel = ob_get_level();
    $data = $generator->generate();
    $content = base64_decode(substr($data['data'], strlen('data:image/png;base64,')), true);
    $image = imagecreatefromstring($content);

    expect(ob_get_level())->toBe($bufferLevel)
        ->and(imagesx($image))->toBe($config->width)
        ->and(imagesy($image))->toBe($config->height)
        ->and(imagecolorsforindex($image, imagecolorat($image, 0, 0))['alpha'])->toBe(127)
        ->and(strlen(count_chars($data['text'], 3)))->toBe(Config::LENGTH)
        ->and(strspn($data['text'], Config::CHARS))->toBe(Config::LENGTH);
})->with(range(1, 6))->with([16, 48]);

it('rejects invalid rules before rendering', function () {
    expect(fn () => Captcha::generate('normal:1'))->toThrow(CaptchaRuleException::class);
});

it('uses the configured rule unless explicitly overridden', function (?string $rule, string $expected) {
    config(['pin.captcha.cache_enabled' => false]);
    $generator = new CaptchaGenerator(new Config(['rule' => 'rev']));
    $data = $generator->generate($rule);

    expect(app('pin.captcha.token')->decode($data['token'])->rule)->toBe($expected);
})->with([
    [null, 'rev'],
    ['', 'normal'],
    ['first:2', 'first:2'],
]);
