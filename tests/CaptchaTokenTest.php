<?php

declare(strict_types=1);

use Pin\Captcha\Captcha;

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
