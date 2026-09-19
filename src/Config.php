<?php

declare(strict_types=1);

namespace Pin\Captcha;

use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * 验证码配置类
 */
class Config
{
    /**
     * 默认验证码长度
     */
    public const int LENGTH = 4;

    /**
     * 默认验证码字符集（去除易混淆字符：0O1l等）
     */
    public const string CHARS = '2345678abcdefhijkmnpqrstuvwxyzABCDEFGHJKLMNPQRTUVWXY';

    /**
     * 验证码验证规则
     *
     * @see Rule
     */
    public ?string $rule = null;

    /**
     * 验证码图片宽度（px）
     */
    public ?int $width = null;

    /**
     * 验证码图片高度（px）
     */
    public ?int $height = null;

    /**
     * 字符最大倾斜角度（单位：度）
     */
    public int $angle = 40;

    /**
     * 字体文件路径（.ttf）
     */
    public ?string $font = null;

    /**
     * 字体大小
     */
    public int $fontSize = 16;

    /**
     * 验证码过期时间（秒）
     *
     * 默认：300 秒（5分钟）
     */
    public int $expires = 300;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(array $config = [])
    {
        foreach ($config as $key => $value) {
            // 支持 snake_case 转 camelCase（如 font_size -> fontSize）
            $key = Str::camel($key);
            if (! property_exists($this, $key)) {
                throw new InvalidArgumentException(sprintf('未知验证码配置 "%s"', $key));
            }

            $this->{$key} = $value;
        }

        $this->initialize();
    }

    /**
     * 初始化默认配置
     */
    protected function initialize(): void
    {
        foreach (['fontSize', 'expires'] as $key) {
            if ($this->{$key} <= 0) {
                throw new InvalidArgumentException(sprintf('验证码配置 "%s" 必须大于 0', $key));
            }
        }

        foreach (['width', 'height'] as $key) {
            if ($this->{$key} !== null && $this->{$key} <= 0) {
                throw new InvalidArgumentException(sprintf('验证码配置 "%s" 必须大于 0', $key));
            }
        }

        if ($this->angle < 0) {
            throw new InvalidArgumentException('验证码配置 "angle" 不能小于 0');
        }

        // 随机字体（fonts/1.ttf ~ fonts/6.ttf）
        $this->font ??= __DIR__.'/fonts/'.random_int(1, 6).'.ttf';

        if (! is_file($this->font) || ! is_readable($this->font)) {
            throw new InvalidArgumentException(sprintf('验证码字体不可读：%s', $this->font));
        }

        // 根据字体大小自动计算宽度（字符数 + 间距）
        $this->width ??= $this->fontSize * (static::LENGTH + 1);

        // 根据字体大小自动计算高度
        $this->height ??= (int) ($this->fontSize * 2.5);
    }
}
