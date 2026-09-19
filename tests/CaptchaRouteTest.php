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

it('protects generated response fields when merging extras', function () {
    config(['pin.captcha.routes.generate.extras' => [
        'text' => 'do not expose',
        'token' => 'do not override',
        'width' => 0,
        'custom' => 'extra value',
    ]]);

    $data = CaptchaRoute::Generate->testJson($this)->assertOk()->json('data');

    expect($data)->not()->toHaveKey('text')
        ->and($data['token'])->not()->toBe('do not override')
        ->and($data['width'])->toBeGreaterThan(0)
        ->and($data['custom'])->toBe('extra value');
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
