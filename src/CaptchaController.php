<?php

declare(strict_types=1);

namespace Pin\Captcha;

use Dedoc\Scramble\Attributes\Group;
use Pin\Http\ApiResponse;
use Pin\Http\Controller;

/**
 * 验证码接口控制器
 */
#[Group('验证码')]
class CaptchaController extends Controller
{
    /**
     * 验证码
     *
     * @return ApiResponse<array{token: string, data: string, width: int, height: int, enabled?: bool}>
     */
    public function generate(): ApiResponse
    {
        $data = Captcha::generate(null, app()->request->query('theme') === 'dark');
        $data = array_merge(config('pin.captcha.routes.generate.extras', []), $data);
        unset($data['text']);

        return $this->success($data);
    }

    /**
     * 可用的验证码规则
     *
     * @return ApiResponse<array{
     *      label: string,
     *      value: string,
     *      has_param: bool,
     *   }[]>
     */
    public function availableRules(): ApiResponse
    {
        $rules = array_map(static function (array $item): array {
            return [
                'label' => $item['label'],
                'value' => $item['rule'],
                'has_param' => $item['has_param'],
            ];
        }, Rule::all());

        array_unshift($rules, ['label' => '默认', 'value' => '', 'has_param' => false]);

        return $this->success($rules);
    }
}
