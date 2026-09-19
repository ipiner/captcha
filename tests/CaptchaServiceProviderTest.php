<?php

declare(strict_types=1);

use Illuminate\Config\Repository;
use Illuminate\Foundation\Application;
use Pin\Captcha\Captcha;
use Pin\Captcha\CaptchaGenerator;
use Pin\Captcha\CaptchaServiceProvider;
use Pin\Captcha\CaptchaToken;
use Pin\Captcha\CaptchaValidator;
use Pin\Captcha\Config;

it('registers services and configuration before booting', function () {
    $application = new Application(dirname(__DIR__));
    $application->instance('config', new Repository(['pin' => ['captcha' => ['config' => ['font_size' => 24]]]]));
    $provider = new CaptchaServiceProvider($application);
    $provider->register();

    expect($application->make(Config::class)->fontSize)->toBe(24)
        ->and($application->make(Config::class)->expires)->toBe(300)
        ->and($application->make(CaptchaGenerator::class))->toBe($application->make('pin.captcha.generator'));
});

it('resolves class names and legacy service names to the same instances', function (string $class, string $service) {
    expect(app($class))->toBe(app($service));
})->with([
    [Captcha::class, 'pin.captcha'],
    [CaptchaGenerator::class, 'pin.captcha.generator'],
    [CaptchaToken::class, 'pin.captcha.token'],
    [CaptchaValidator::class, 'pin.captcha.validator'],
]);
