<?php


namespace App\Service;


use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;

class Logger
{
    private static $requireId;

    private static function requestId()
    {
        if (is_null(self::$requireId)) {
            self::$requireId = $_SERVER['X_REQUEST_ID'] ?? Uuid::uuid4()->toString();
        }
        return self::$requireId;
    }

    public static function info(string $message, array $context = [])
    {
        $traces = debug_backtrace(
            DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS,
            1
        );
        if (!empty($traces)) {
            $context['file'] = str_replace(base_path(), '', $traces[0]['file']);
            $context['line'] = $traces[0]['line'];
        }
        $context['request_id'] = self::requestId();
        Log::info($message, $context);
    }

    public static function error(string $message, array $context = [])
    {
        $traces = debug_backtrace(
            DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS,
            1
        );
        if (!empty($traces)) {
            $context['file'] = str_replace(base_path(), '', $traces[0]['file']);
            $context['line'] = $traces[0]['line'];
        }
        $context['request_id'] = self::requestId();
        Log::error($message, $context);
    }
}
