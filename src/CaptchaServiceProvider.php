<?php

declare(strict_types=1);

namespace Pin\Captcha;

use Pin\Errors\Registry;
use Pin\Support\ServiceProvider;

/**
 * 验证码服务提供者。
 */
class CaptchaServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        Registry::register(Errors::cases());

        $this->mergeConfigFrom(__DIR__.'/../config/captcha.php', 'pin.captcha'); 
        $this->publishes([
            __DIR__.'/../config/captcha.php' => config_path('pin/captcha.php'),
        ], 'pin-captcha-config');

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'pin-captcha');
        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/pin'),
        ], 'pin-captcha-errors');

        $this->app->singleton('pin.captcha', Captcha::class);
        $this->app->singleton('pin.captcha.token', CaptchaToken::class);
        $this->app->singleton('pin.captcha.validator', CaptchaValidator::class);

        $this->app->singleton(
            'pin.captcha.generator',
            fn () => new CaptchaGenerator(new Config(config('pin.captcha.config')))
        );

        // 自动注册验证码路由
        if (config('pin.captcha.routes.enabled')) {
            CaptchaRoute::registerRoutes();
        }
    }
}
