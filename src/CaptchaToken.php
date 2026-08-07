<?php

declare(strict_types=1);

namespace Pin\Captcha;

use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Redis;
use Pin\Support\Json;
use Pin\Token\Token;

/**
 * 验证码 Token 管理器
 */
class CaptchaToken
{
    /**
     * 解码验证码 Token
     */
    public function decode(string $encoded): Token
    {
        $token = \Pin\Support\Facades\Token::decode($encoded);
        if (! static::isCacheEnabled()) {
            return $token;
        }

        $key = static::key($token->jti);
        $data = static::connection()->get($key);
        if (! $data) {
            throw new CaptchaException(Errors::CaptchaExpired);
        }
        static::connection()->del($key);

        return new Token(Json::decode($data), $encoded);
    }

    /**
     * 编码验证码 Token
     */
    public function encode(string $text, string $rule, int $ttl): string
    {
        $data = compact('text', 'rule');
        if (! static::isCacheEnabled()) {
            return \Pin\Support\Facades\Token::encode($data, $ttl);
        }

        $key = uniqid();
        static::connection()->setex(static::key($key), $ttl, Json::encode($data));

        return \Pin\Support\Facades\Token::encode(['jti' => $key], $ttl);
    }

    /**
     * 获取 Redis 连接
     */
    protected function connection(): Connection
    {
        return Redis::connection('default');
    }

    /**
     * 是否启用缓存
     */
    protected function isCacheEnabled(): bool
    {
        return config('pin.captcha.cache_enabled');
    }

    /**
     * 生成 Redis 存储 Key
     */
    protected function key(string $key): string
    {
        return 'captcha:'.$key;
    }
}
