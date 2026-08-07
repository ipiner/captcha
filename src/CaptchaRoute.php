<?php

declare(strict_types=1);

namespace Pin\Captcha;

use Pin\Route\Attributes\Title;
use Pin\Route\InteractsWithRoute;
use Pin\Route\Routable;

/**
 * 验证码路由定义
 */
enum CaptchaRoute: string implements Routable
{
    use InteractsWithRoute;

    #[Title('生成验证码')]
    case Generate = 'GET:/api/captcha';

    #[Title('可用验证码规则')]
    case AvailableRules = 'GET:/api/captcha/rules';

    /**
     * 注册验证码相关路由
     */
    public static function registerRoutes(): void
    {
        self::Generate->register(
            [CaptchaController::class, 'generate'],
        );
        self::AvailableRules->register(
            [CaptchaController::class, 'availableRules'],
            'auth',
        );
    }
}
