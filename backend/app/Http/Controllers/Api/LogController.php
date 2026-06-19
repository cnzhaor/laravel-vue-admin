<?php

namespace App\Http\Controllers\Api;

use App\Models\LoginLog;
use App\Models\OperationLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogController extends ApiController
{
    public function operations(Request $request): JsonResponse
    {
        return $this->success(OperationLog::query()->latest()->paginate($request->integer('per_page', 15)));
    }

    public function logins(Request $request): JsonResponse
    {
        return $this->success(LoginLog::query()->latest()->paginate($request->integer('per_page', 15)));
    }
}

