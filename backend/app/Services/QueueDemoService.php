<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class QueueDemoService
{
    private const CACHE_TTL_MINUTES = 60;

    private const LOCK_SECONDS = 5;

    private const LOCK_WAIT_SECONDS = 2;

    /**
     * @return array<string, int|string|null>
     */
    public function create(int $userId, string $message, int $delaySeconds): array
    {
        $createdAt = CarbonImmutable::now('UTC');
        $task = [
            'id' => (string) Str::uuid(),
            'user_id' => $userId,
            'message' => $message,
            'delay_seconds' => $delaySeconds,
            'status' => 'queued',
            'result' => null,
            'created_at' => $createdAt->toIso8601String(),
            'available_at' => $createdAt->addSeconds($delaySeconds)->toIso8601String(),
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

    public function markProcessing(string $taskId): bool
    {
        return $this->transition($taskId, ['queued', 'processing'], [
            'status' => 'processing',
            'started_at' => now()->utc()->toIso8601String(),
        ]);
    }

    public function markCompleted(string $taskId, string $result): bool
    {
        return $this->transition($taskId, ['processing'], [
            'status' => 'completed',
            'result' => $result,
            'finished_at' => now()->utc()->toIso8601String(),
        ]);
    }

    public function markFailed(string $taskId, string $message): bool
    {
        return $this->transition($taskId, ['queued', 'processing'], [
            'status' => 'failed',
            'result' => $message,
            'finished_at' => now()->utc()->toIso8601String(),
        ]);
    }

    /**
     * @param  list<string>  $allowedStatuses
     * @param  array<string, int|string|null>  $changes
     */
    private function transition(string $taskId, array $allowedStatuses, array $changes): bool
    {
        return Cache::lock($this->lockKey($taskId), self::LOCK_SECONDS)
            ->block(self::LOCK_WAIT_SECONDS, function () use ($taskId, $allowedStatuses, $changes): bool {
                $task = $this->find($taskId);

                if ($task === null || ! in_array($task['status'], $allowedStatuses, true)) {
                    return false;
                }

                if ($task['status'] === $changes['status']) {
                    return true;
                }

                $this->store(array_merge($task, $changes));

                return true;
            });
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

    private function lockKey(string $taskId): string
    {
        return "queue-demo:task:{$taskId}:lock";
    }
}
