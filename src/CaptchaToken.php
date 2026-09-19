<?php

declare(strict_types=1);

namespace Pin\Captcha;

use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Redis;
use InvalidArgumentException;
use JsonException;
use Pin\Support\Facades\Token as TokenFacade;
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
        $token = TokenFacade::decode($encoded);
        if (! $this->isCacheEnabled()) {
            $this->validatePayload($token->payload->toArray());

            return $token;
        }

        if (! isset($token->jti) || ! is_string($token->jti) || $token->jti === '') {
            throw new CaptchaException(Errors::CaptchaTokenInvalid);
        }

        $connection = $this->connection();
        $key = $this->key($token->jti);
        $cached = $connection->get($key);

        if ($cached === null || $cached === false) {
            throw new CaptchaException(Errors::CaptchaMissing);
        }

        $connection->del($key);

        try {
            $data = json_decode($cached, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new CaptchaException(Errors::CaptchaTokenInvalid, $exception);
        }

        $this->validatePayload($data);
        $token->text = $data['text'];
        $token->rule = $data['rule'];

        return $token;
    }

    /**
     * 编码验证码 Token
     */
    public function encode(string $text, string $rule, int $ttl): string
    {
        if ($ttl <= 0) {
            throw new InvalidArgumentException('验证码有效期必须大于 0');
        }

        $data = compact('text', 'rule');
        $this->validatePayload($data);

        if (! $this->isCacheEnabled()) {
            return TokenFacade::encode($data, $ttl);
        }

        $tokenId = bin2hex(random_bytes(16));
        $encoded = TokenFacade::encode(['jti' => $tokenId], $ttl);
        $this->connection()->setex($this->key($tokenId), $ttl, Json::encode($data));

        return $encoded;
    }

    /**
     * 缓存和无缓存模式使用相同的载荷约束，避免将其他用途的 Token 当作验证码。
     */
    protected function validatePayload(mixed $data): void
    {
        if (
            ! is_array($data)
            || ! is_string($data['text'] ?? null)
            || $data['text'] === ''
            || ! is_string($data['rule'] ?? null)
        ) {
            throw new CaptchaException(Errors::CaptchaTokenInvalid);
        }
    }

    /**
     * 获取 Redis 连接
     */
    protected function connection(): Connection
    {
        return Redis::connection(config('pin.captcha.redis_connection', 'default'));
    }

    /**
     * 是否启用缓存
     */
    protected function isCacheEnabled(): bool
    {
        return (bool) config('pin.captcha.cache_enabled', true);
    }

    /**
     * 生成 Redis 存储 Key
     */
    protected function key(string $key): string
    {
        return 'captcha:'.$key;
    }
}
