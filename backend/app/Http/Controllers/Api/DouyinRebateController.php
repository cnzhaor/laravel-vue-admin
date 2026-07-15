<?php

namespace App\Http\Controllers\Api;

use App\Services\DouyinRebateService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class DouyinRebateController extends ApiController
{
    public function __construct(private readonly DouyinRebateService $douyin) {}

    public function status(): JsonResponse
    {
        return $this->success($this->douyin->status());
    }

    public function convert(Request $request): JsonResponse
    {
        $data = $request->validate([
            'command' => ['required', 'string', 'max:2000'],
            'external_info' => ['nullable', 'string', 'max:40', 'regex:/^[A-Za-z0-9_]+$/'],
        ]);

        try {
            return $this->success($this->douyin->convert($data['command'], $data['external_info'] ?? null), '转链成功');
        } catch (InvalidArgumentException $exception) {
            return response()->json(['code' => 1, 'message' => $exception->getMessage(), 'data' => null], 422);
        }
    }

    public function bills(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'page_size' => ['sometimes', 'integer', 'min:1', 'max:200'],
        ]);

        try {
            $date = CarbonImmutable::createFromFormat('Y-m-d', $data['date'], 'Asia/Shanghai');
            return $this->success($this->douyin->bills($date, $data['page'] ?? 1, $data['page_size'] ?? 20));
        } catch (InvalidArgumentException $exception) {
            return response()->json(['code' => 1, 'message' => $exception->getMessage(), 'data' => null], 422);
        }
    }
}
