<?php

namespace App\Http\Controllers\Api;

use App\Jobs\ProcessQueueDemo;
use App\Services\QueueDemoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QueueDemoController extends ApiController
{
    public function __construct(private readonly QueueDemoService $tasks) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:100'],
            'delay_seconds' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $task = $this->tasks->create(
            (int) $request->user()->getAuthIdentifier(),
            $data['message'],
            $data['delay_seconds'],
        );

        ProcessQueueDemo::dispatch($task['id']);

        return $this->success($task, '任务已提交到 Redis 队列', 202);
    }

    public function show(Request $request, string $taskId): JsonResponse
    {
        $task = $this->tasks->find($taskId);

        abort_if($task === null, 404, '任务不存在或已过期');
        abort_unless($task['user_id'] === (int) $request->user()->getAuthIdentifier(), 403, '无权查看该任务');

        return $this->success($task);
    }
}
