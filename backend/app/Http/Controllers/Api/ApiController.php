<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

abstract class ApiController extends Controller
{
    protected function success(mixed $data = null, string $message = '操作成功', int $status = 200): JsonResponse
    {
        return response()->json(['code' => 0, 'message' => $message, 'data' => $data], $status);
    }
}

