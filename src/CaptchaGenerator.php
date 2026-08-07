<?php

declare(strict_types=1);

namespace Pin\Captcha;

use GdImage;
use Pin\Token\Token;

/**
 * 图形验证码生成器
 *
 * 基于 GD 扩展
 */
class CaptchaGenerator
{
    /**
     * GD 图像资源
     */
    protected GdImage $im;

    /**
     * 当前画笔颜色
     */
    protected int $color;

    /**
     * @param  Config  $config  验证码配置
     */
    public function __construct(protected Config $config)
    {
    }

    /**
     * 生成验证码
     *
     * @param  string|null  $rule  校验规则，{@see Rule}
     * @param  bool  $dark  是否暗黑模式
     * @return array{
     *     text: string,
     *     width: int,
     *     height: int,
     *     token: string,
     *     data: string
     * }
     */
    public function generate(?string $rule = null, bool $dark = false): array
    {
        // 规则优先级：参数 > 配置 > 默认
        $rule = $rule ?? $this->config->rule;
        $rule = $rule ?: Rule::Normal->value;

        // 创建真彩色画布（支持透明）
        $im = imagecreatetruecolor($this->config->width, $this->config->height);

        // 关闭 alpha 混合（关键）
        imagealphablending($im, false);

        // 开启保存 alpha 通道（关键）
        imagesavealpha($im, true);

        // 创建透明色
        $transparent = imagecolorallocatealpha($im, 0, 0, 0, 127);

        // 填充整个背景为透明
        imagefill($im, 0, 0, $transparent);

        // 字体颜色
        $color = $this->colorAllocate($im, $this->getTextColor($dark));

        // 生成验证码文本
        $text = $this->generateText();

        // 写入文字
        $this->writeText($im, $text, $color);

        // 输出 PNG（内存缓冲）
        ob_start();
        imagepng($im);
        $content = ob_get_clean();

        return [
            'text' => $text,
            'token' => app('pin.captcha.token')->encode(
                $text,
                $rule,
                $this->config->expires
            ),
            'width' => $this->config->width,
            'height' => $this->config->height,
            'data' => 'data:image/png;base64,'.base64_encode($content),
        ];
    }

    /**
     * 分配颜色
     *
     * @param  array{0:int,1:int,2:int}  $rgb  RGB 颜色值
     */
    protected function colorAllocate(GdImage $im, array $rgb): int
    {
        return imagecolorallocate($im, $rgb[0], $rgb[1], $rgb[2]);
    }

    /**
     * 生成验证码文本
     */
    protected function generateText(): string
    {
        return substr(str_shuffle(Config::CHARS), 0, Config::LENGTH);
    }

    /**
     * 随机获取字体颜色
     */
    protected function getTextColor(bool $dark): array
    {
        if ($dark) {
            return [
                random_int(150, 255),
                random_int(150, 255),
                random_int(150, 255),
            ];
        }

        return [
            random_int(1, 150),
            random_int(1, 150),
            random_int(1, 150),
        ];
    }

    /**
     * 计算字符 X 坐标
     *
     * 根据字符索引均匀分布
     */
    protected function getTextX(int $index): int
    {
        return intval($this->config->fontSize * $index + $this->config->fontSize / 2);
    }

    /**
     * 计算字符 Y 坐标（带随机偏移）
     *
     * 用于增加验证码抗识别能力
     */
    protected function getTextY(): int
    {
        $random = random_int(
            intval($this->config->fontSize / 2),
            min(20, $this->config->fontSize)
        );

        return $this->config->fontSize + $random;
    }

    /**
     * 绘制验证码文本
     */
    protected function writeText(GdImage $im, string $text, int $color): void
    {
        // 随机旋转角度
        $angle = random_int(-$this->config->angle, $this->config->angle);

        foreach (str_split($text) as $index => $char) {
            imagettftext(
                $im,
                $this->config->fontSize,
                $angle,
                $this->getTextX($index),
                $this->getTextY(),
                $color,
                $this->config->font,
                $char
            );
        }
    }
}
