<?php

declare(strict_types=1);

use Pin\Captcha\Config;

it('initializes dimensions from the configured font size', function (string $fontSizeKey) {
    $config = new Config([$fontSizeKey => 24]);

    expect($config->fontSize)->toBe(24)
        ->and($config->width)->toBe(120)
        ->and($config->height)->toBe(60)
        ->and(is_readable($config->font))->toBeTrue();
})->with(['font_size', 'fontSize']);

it('preserves explicit image dimensions and font', function () {
    $font = dirname(__DIR__).'/src/fonts/1.ttf';
    $config = new Config(['width' => 180, 'height' => 80, 'font' => $font]);

    expect($config->width)->toBe(180)
        ->and($config->height)->toBe(80)
        ->and($config->font)->toBe($font);
});

it('rejects invalid configuration before rendering', function (array $config) {
    expect(fn () => new Config($config))->toThrow(InvalidArgumentException::class);
})->with([
    'unknown option' => [['font_szie' => 16]],
    'zero width' => [['width' => 0]],
    'negative height' => [['height' => -1]],
    'zero font size' => [['font_size' => 0]],
    'negative font size' => [['font_size' => -1]],
    'negative angle' => [['angle' => -1]],
    'zero expiration' => [['expires' => 0]],
    'negative expiration' => [['expires' => -1]],
    'missing font' => [['font' => __DIR__.'/missing.ttf']],
    'directory as font' => [['font' => __DIR__]],
]);

it('initializes defaults without configuration', function () {
    $config = new Config();

    expect($config->width)->toBe(80)
        ->and($config->height)->toBe(40)
        ->and($config->expires)->toBe(300);
});
