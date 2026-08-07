<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Pin\Captcha\CaptchaRoute;

it('generates captcha without text/enabled fields', function () {
    $data = CaptchaRoute::Generate->testJson($this)->json('data');

    expect($data)
        ->toHaveKey('token')
        ->not()->toHaveKey('text')
        ->not()->toHaveKey('enabled');
});

it('returns disabled flag when captcha extras disabled', function () {
    config([
        'pin.captcha.routes.generate.extras.enabled' => false,
    ]);

    CaptchaRoute::Generate->testJson($this)->assertJsonPath('data.enabled', false);
});

it('authorizes available rules access', function (bool $actingAs, int $status) {
    $test = $actingAs ? $this->actingAs(new GenericUser([])) : $this;

    CaptchaRoute::AvailableRules
        ->testJson($test)
        ->assertStatus($status);

})->with([
    'guest user' => [
        'actingAs' => false,
        'status' => 401,
    ],

    'authenticated user' => [
        'actingAs' => true,
        'status' => 200,
    ],
]);
