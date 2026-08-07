<?php

return [
    'config' => [
        /**
         * 验证码图片宽度（像素）
         *
         * 为 null 时自动根据字符数量与字体大小计算。
         */
        'width' => null,

        /**
         * 验证码图片高度（像素）
         *
         * 为 null 时自动根据字体大小适配。
         */
        'height' => null,

        /**
         * 字符随机倾斜角度（度）
         *
         * 数值越大：
         * - 字符倾斜越明显
         * - OCR 识别难度越高
         */
        'angle' => 40,

        /**
         * 验证码字体文件（TTF）
         *
         * 指定验证码使用的字体文件路径。
         *
         * 为 null 时自动随机选择字体
         */
        'font' => null,

        /**
         * 验证码字体大小
         *
         * 数值越大：
         * - 字符越清晰
         * - 图片占用空间越大
         */
        'font_size' => 16,

        /**
         * 验证码有效时间（秒）
         */
        'expires' => 300,
    ],

    /**
     * 验证码路由配置
     */
    'routes' => [

        /**
         * 是否启用内置验证码路由
         *
         * 启用后自动注册：
         *  - GET /api/captcha
         *  - GET /api/captcha/rules
         */
        'enabled' => true,

        /**
         * 验证码生成接口配置
         */
        'generate' => [

            /**
             * 额外响应数据
             *
             * 示例：
             *
             * ```
             * 'extras' => [
             *     'enabled' => true
             * ],
             * ```
             */
            'extras' => [],
        ],
    ],
    /**
     * 是否启用缓存
     *
     * 启用时，验证码只检验一次
     */
    'cache_enabled' => env('CAPTCHA_CACHE_ENABLED', true),
];
