<?php

namespace App\Service;

/**
 * 错误码
 *
 * Class ErrorCode
 * @package App\Service
 */
class ErrorCode
{
    const PARAMS_INVALID = 1400;
    const SYSTEM_ERROR = 1500;

    public static $message = [
        self::PARAMS_INVALID => 'params invalid',
        self::SYSTEM_ERROR => 'system error',
    ];

    /**
     * 获取错误提示
     *
     * @param int $code
     * @return string
     */
    public static function getMessage(int $code): string
    {
        return self::$message[$code] ?? 'unknown error';
    }
}
