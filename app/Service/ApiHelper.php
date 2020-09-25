<?php

namespace App\Service;

use Illuminate\Http\JsonResponse;

class ApiHelper
{
    /**
     * 响应成功
     *
     * @param array $data
     * @return JsonResponse
     */
    public static function apiSuccess($data = []): JsonResponse
    {
        return response()->json(
            [
                'code' => 0,
                'message' => 'success',
                'data' => $data
            ]
        );
    }

    /**
     * 响应失败
     *
     * @param int $code
     * @param string $message
     * @return JsonResponse
     */
    public static function apiFail(int $code, string $message = ''): JsonResponse
    {
        return response()->json(
            [
                'code' => $code,
                'message' => empty($message) ? ErrorCode::getMessage($code) : $message
            ]
        );
    }
}
