<?php

namespace Tests\Feature;

use Illuminate\Queue\Events\QueueBusy;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class QueueConfigurationTest extends TestCase
{
    public function test_redis_worker_safety_defaults_are_configured(): void
    {
        $this->assertSame(90, config('queue.connections.redis.retry_after'));
        $this->assertSame(5, config('queue.connections.redis.block_for'));
        $this->assertTrue(config('queue.connections.redis.after_commit'));
        $this->assertSame(100, config('queue.monitor.max_jobs'));
    }

    public function test_busy_queue_event_writes_a_structured_warning(): void
    {
        Log::spy();

        event(new QueueBusy('redis', 'default', 101));

        Log::shouldHaveReceived('warning')
            ->once()
            ->with('队列积压超过阈值', [
                'connection' => 'redis',
                'queue' => 'default',
                'jobs' => 101,
            ]);
    }
}
