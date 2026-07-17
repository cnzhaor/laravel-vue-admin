<?php

namespace App\Jobs;

use App\Services\QueueDemoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessQueueDemo implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    /** @var array<int, int> */
    public array $backoff = [2, 5];

    public function __construct(public readonly string $taskId) {}

    public function handle(QueueDemoService $tasks): void
    {
        $task = $tasks->find($this->taskId);

        if ($task === null) {
            return;
        }

        if (! $tasks->markProcessing($this->taskId)) {
            return;
        }

        $tasks->markCompleted(
            $this->taskId,
            "Worker 已执行延迟任务：{$task['message']}",
        );
    }

    public function failed(?Throwable $_exception): void
    {
        app(QueueDemoService::class)->markFailed(
            $this->taskId,
            '任务执行失败，请联系管理员',
        );
    }
}
