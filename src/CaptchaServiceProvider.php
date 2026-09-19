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
     * 注册配置与服务，允许其他服务提供者在 boot 阶段解析验证码服务。
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/captcha.php', 'pin.captcha');

        $this->app->singleton(Captcha::class);
        $this->app->singleton(CaptchaToken::class);
        $this->app->singleton(CaptchaValidator::class);
        $this->app->singleton(Config::class, fn ($app) => new Config($app['config']->get('pin.captcha.config', [])));
        $this->app->singleton(
            CaptchaGenerator::class,
            fn ($app) => new CaptchaGenerator($app->make(Config::class))
        );

        $this->app->alias(Captcha::class, 'pin.captcha');
        $this->app->alias(CaptchaToken::class, 'pin.captcha.token');
        $this->app->alias(CaptchaValidator::class, 'pin.captcha.validator');
        $this->app->alias(CaptchaGenerator::class, 'pin.captcha.generator');
    }

    public function boot(): void
    {
        Registry::register(Errors::cases());

        $this->publishes([
            __DIR__.'/../config/captcha.php' => config_path('pin/captcha.php'),
        ], 'pin-captcha-config');

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'pin-captcha');
        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/pin-captcha'),
        ], 'pin-captcha-errors');

        // 自动注册验证码路由
        if (config('pin.captcha.routes.enabled') && ! $this->app->routesAreCached()) {
            CaptchaRoute::registerRoutes();
        }
    }
}
