<?php

declare(strict_types=1);

namespace Pin\Tests;

use Pin\Captcha\CaptchaServiceProvider;

class TestCase extends \Pin\Testing\TestCase
{
    protected function providers(): array
    {
        return [
            ...parent::providers(),
            CaptchaServiceProvider::class,
        ];
    }
}
