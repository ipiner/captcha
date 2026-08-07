<?php

declare(strict_types=1);

namespace Pin\Captcha;

/**
 * 验证码规则枚举
 */
enum Rule: string
{
    /**
     * 正常验证
     *
     * 直接按原始验证码内容进行比对
     *
     * 示例：6809 => 输入 6809
     */
    case Normal = 'normal';

    /**
     * 倒序验证
     *
     * 将验证码字符串反转后进行验证
     *
     * 示例：6809 => 输入 9086
     */
    case Rev = 'rev';

    /**
     * 按指定顺序验证
     *
     * param：
     * - 由 1~4 组成的排列字符串
     * - 表示验证码字符重新排序的索引规则
     *
     * 示例：
     * - 验证码：6809
     * - order:2134 => 8609
     */
    case Order = 'order';

    /**
     * 验证前 N 位
     *
     * param：
     * - N（数字）
     *
     * 示例：6809 + first:2 => 68
     */
    case FirstN = 'first';

    /**
     * 验证后 N 位
     *
     * param：
     * - N（数字）
     *
     * 示例：6809 + last:2 => 09
     */
    case LastN = 'last';

    /**
     * 前置拼接
     *
     * param：
     * - n（第n位字符，1-based）
     *
     * 示例：6809 + prepend:2 => 86809
     */
    case PrependN = 'prepend';

    /**
     * 后置拼接
     *
     * param：
     * - n（第n位字符，1-based）
     *
     * 示例：6809 + append:2 => 68098
     */
    case AppendN = 'append';

    /**
     * 固定值验证
     *
     * param：
     * - 固定字符串（字母+数字）
     *
     * 示例：fixed:abcd => 用户必须输入 abcd
     */
    case Fixed = 'fixed';

    /**
     * 规则列表
     */
    public static function all(): array
    {
        return array_map(
            fn (self $r) => [
                'rule' => $r->value,
                'label' => $r->label(),
                'has_param' => $r->hasParam(),
            ],
            self::cases()
        );
    }

    /**
     * 解析规则字符串
     *
     * 返回：
     *
     * [Rule $rule, string|null $param]
     *
     * @throws CaptchaRuleException
     */
    public static function parse(string $input): array
    {
        // 无参数规则匹配（如 normal / rev）
        foreach (self::cases() as $rule) {
            if (! $rule->hasParam() && $rule->value === $input) {
                return [$rule, null];
            }
        }

        // 有参数规则解析
        $parts = explode(':', $input, 2);

        if (count($parts) !== 2) {
            throw new CaptchaRuleException(sprintf('非法规则 "%s"', $input));
        }

        [$key, $param] = $parts;

        $rule = self::tryFrom($key);

        if (! $rule) {
            throw new CaptchaRuleException(sprintf('未知规则 "%s"', $key));
        }

        // 参数校验（按规则类型）
        $rule->validateParam($param);

        return [$rule, $param];
    }

    /**
     * 规则中文名称
     */
    public function label(): string
    {
        return match ($this) {
            self::Normal => '正常验证',
            self::Rev => '倒序验证',
            self::Order => '按指定顺序验证',
            self::FirstN => '验证前n位',
            self::LastN => '验证后n位',
            self::PrependN => '第n位验证码 + 验证码',
            self::AppendN => '验证码 + 第n位验证码',
            self::Fixed => '固定值',
        };
    }

    /**
     * 是否包含参数
     */
    private function hasParam(): bool
    {
        return ! in_array($this, [self::Normal, self::Rev]);
    }

    /**
     * 固定值校验规则
     *
     * 仅允许字母和数字
     *
     * @throws CaptchaRuleException
     */
    private function validateFixed(string $param): void
    {
        if (! preg_match('/^[a-zA-Z0-9]+$/', $param)) {
            throw new CaptchaRuleException(
                sprintf('规则 "%s" 错误: 只能由数字和字母组成', $this->label())
            );
        }
    }

    /**
     * 枚举范围校验
     *
     * 适用于 first / last / prepend / append
     *
     * @throws CaptchaRuleException
     */
    private function validateIn(string $param, array $allow): void
    {
        if (! in_array($param, $allow, true)) {
            throw new CaptchaRuleException(
                sprintf('规则 "%s" 错误: 参数必须为 %s', $this->label(), implode('/', $allow))
            );
        }
    }

    /**
     * order 规则校验
     *
     * - 每一位表示目标位置索引（1-based）
     * - 例如 "2134" 表示：第2位、第1位、第3位、第4位
     *
     * @throws CaptchaRuleException
     */
    private function validateOrder(string $param): void
    {
        if (! preg_match('/^[1234]{1,5}$/', $param)) {
            throw new CaptchaRuleException(
                sprintf('规则 "%s" 错误: 参数必须为1-5位的1234组合', $this->label())
            );
        }
    }

    /**
     * 参数校验入口
     *
     * Normal / Rev 不会进入该逻辑
     */
    private function validateParam(string $param): void
    {
        match ($this) {
            self::Order => $this->validateOrder($param),
            self::FirstN, self::LastN => $this->validateIn($param, ['1', '2', '3']),
            self::PrependN, self::AppendN => $this->validateIn($param, ['1', '2', '3', '4']),
            self::Fixed => $this->validateFixed($param),
        };
    }
}
