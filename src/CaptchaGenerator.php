<?php

declare(strict_types=1);

namespace Pin\Captcha;

use GdImage;
use RuntimeException;

/**
 * 图形验证码生成器
 *
 * 基于 GD 扩展
 */
class CaptchaGenerator
{
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
        $rule = ($rule ?? $this->config->rule) ?: Rule::Normal->value;
        Rule::parse($rule);

        $image = $this->createImage();
        $color = $this->colorAllocate($image, $this->getTextColor($dark));
        $text = $this->generateText();
        $this->writeText($image, $text, $color);
        $content = $this->encodeImage($image);

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
     * 关闭混合并保留 alpha 通道，使 PNG 背景在明暗主题下均保持透明。
     */
    protected function createImage(): GdImage
    {
        $image = imagecreatetruecolor($this->config->width, $this->config->height);
        if ($image === false) {
            throw new RuntimeException('无法创建验证码画布');
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));

        return $image;
    }

    /**
     * 无论 PNG 编码是否成功，都恢复调用方的输出缓冲层级。
     */
    protected function encodeImage(GdImage $image): string
    {
        ob_start();

        try {
            if (! imagepng($image)) {
                throw new RuntimeException('无法编码验证码图片');
            }

            return ob_get_contents();
        } finally {
            ob_end_clean();
        }
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
        $characters = Config::CHARS;
        $lastIndex = strlen($characters) - 1;
        $text = '';

        // 只抽取所需字符，保留字符不重复的行为，并使用安全随机源。
        for ($index = 0; $index < Config::LENGTH; $index++) {
            $selectedIndex = random_int(0, $lastIndex);
            $text .= $characters[$selectedIndex];
            $characters[$selectedIndex] = $characters[$lastIndex--];
        }

        return $text;
    }

    /**
     * 随机获取字体颜色
     *
     * @return array{int, int, int}
     */
    protected function getTextColor(bool $dark): array
    {
        [$min, $max] = $dark ? [150, 255] : [1, 150];

        return [
            random_int($min, $max),
            random_int($min, $max),
            random_int($min, $max),
        ];
    }

    /**
     * 计算字符 X 坐标
     *
     * 根据字符索引均匀分布
     */
    protected function getTextX(int $index): int
    {
        return $this->config->fontSize * $index + intdiv($this->config->fontSize, 2);
    }

    /**
     * 计算字符 Y 坐标（带随机偏移）
     *
     * 用于增加验证码抗识别能力
     */
    protected function getTextY(): int
    {
        $minOffset = intdiv($this->config->fontSize, 2);
        $maxOffset = max($minOffset, min(20, $this->config->fontSize));

        return min($this->config->height - 1, $this->config->fontSize + random_int($minOffset, $maxOffset));
    }

    /**
     * 绘制验证码文本
     */
    protected function writeText(GdImage $im, string $text, int $color): void
    {
        foreach (str_split($text) as $index => $char) {
            imagettftext(
                $im,
                $this->config->fontSize,
                random_int(-$this->config->angle, $this->config->angle),
                $this->getTextX($index),
                $this->getTextY(),
                $color,
                $this->config->font,
                $char
            );
        }
    }
}
