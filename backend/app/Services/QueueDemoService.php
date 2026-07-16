<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class QueueDemoService
{
    private const CACHE_TTL_MINUTES = 60;

    /**
     * @return array<string, int|string|null>
     */
    public function create(int $userId, string $message, int $delaySeconds): array
    {
        $task = [
            'id' => (string) Str::uuid(),
            'user_id' => $userId,
            'message' => $message,
            'delay_seconds' => $delaySeconds,
            'status' => 'queued',
            'result' => null,
            'created_at' => now()->utc()->toIso8601String(),
            'started_at' => null,
            'finished_at' => null,
        ];

        $this->store($task);

        return $task;
    }

    /**
     * @return array<string, int|string|null>|null
     */
    public function find(string $taskId): ?array
    {
        $task = Cache::get($this->key($taskId));

        return is_array($task) ? $task : null;
    }

    public function markProcessing(string $taskId): void
    {
        $this->update($taskId, [
            'status' => 'processing',
            'started_at' => now()->utc()->toIso8601String(),
        ]);
    }

    public function markCompleted(string $taskId, string $result): void
    {
        $this->update($taskId, [
            'status' => 'completed',
            'result' => $result,
            'finished_at' => now()->utc()->toIso8601String(),
        ]);
    }

    public function markFailed(string $taskId, string $message): void
    {
        $this->update($taskId, [
            'status' => 'failed',
            'result' => $message,
            'finished_at' => now()->utc()->toIso8601String(),
        ]);
    }

    /**
     * @param  array<string, int|string|null>  $changes
     */
    private function update(string $taskId, array $changes): void
    {
        $task = $this->find($taskId);

        if ($task === null) {
            return;
        }

        $this->store(array_merge($task, $changes));
    }

    /**
     * @param  array<string, int|string|null>  $task
     */
    private function store(array $task): void
    {
        Cache::put(
            $this->key((string) $task['id']),
            $task,
            now()->addMinutes(self::CACHE_TTL_MINUTES),
        );
    }

    private function key(string $taskId): string
    {
        return "queue-demo:task:{$taskId}";
    }
}
