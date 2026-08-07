<?php

declare(strict_types=1);

namespace Pin\Captcha;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

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
    public ?int $width;

    /**
     * 验证码图片高度（px）
     */
    public ?int $height;

    /**
     * 字符最大倾斜角度（单位：度）
     */
    public int $angle = 40;

    /**
     * 字体文件路径（.ttf）
     */
    public ?string $font;

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

    public function __construct(array $config)
    {
        foreach ($config as $key => $value) {
            // 支持 snake_case 转 camelCase（如 font_size -> fontSize）
            $key = Str::camel($key);
            $this->{$key} = $value;
        }

        $this->initialize();
    }

    /**
     * 初始化默认配置
     */
    protected function initialize(): void
    {
        // 随机字体（fonts/1.ttf ~ fonts/6.ttf）
        $this->font = $this->font ?? (__DIR__.'/fonts/'.Arr::random(range(1, 6)).'.ttf');

        // 根据字体大小自动计算宽度（字符数 + 间距）
        $this->width = $this->width ?? intval($this->fontSize * (static::LENGTH + 1));

        // 根据字体大小自动计算高度
        $this->height = $this->height ?? intval($this->fontSize * 2.5);
    }
}
