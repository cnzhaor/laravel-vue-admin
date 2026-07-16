<?php

namespace Tests\Feature;

use App\Jobs\ProcessQueueDemo;
use App\Models\User;
use App\Services\QueueDemoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class QueueDemoTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_dispatch_demo_job(): void
    {
        Queue::fake();
        Sanctum::actingAs($user = User::factory()->create(['username' => 'queue-demo-owner']));

        $response = $this->postJson('/api/v1/queue-demo/jobs', [
            'message' => '演示任务',
            'delay_seconds' => 3,
        ]);

        $response->assertAccepted()
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.status', 'queued');

        $taskId = $response->json('data.id');

        Queue::assertPushed(
            ProcessQueueDemo::class,
            fn (ProcessQueueDemo $job) => $job->taskId === $taskId,
        );
        $this->getJson("/api/v1/queue-demo/jobs/{$taskId}")
            ->assertOk()
            ->assertJsonPath('data.message', '演示任务');
    }

    public function test_user_cannot_read_another_users_demo_job(): void
    {
        $owner = User::factory()->create(['username' => 'queue-demo-owner']);
        $viewer = User::factory()->create(['username' => 'queue-demo-viewer']);
        $task = app(QueueDemoService::class)->create($owner->id, '私有任务', 1);

        Sanctum::actingAs($viewer);

        $this->getJson("/api/v1/queue-demo/jobs/{$task['id']}")->assertForbidden();
    }

    public function test_demo_job_updates_status_to_completed(): void
    {
        $task = app(QueueDemoService::class)->create(1, '无等待测试', 0);

        (new ProcessQueueDemo($task['id']))->handle(app(QueueDemoService::class));

        $stored = app(QueueDemoService::class)->find($task['id']);

        $this->assertNotNull($stored);
        $this->assertSame('completed', $stored['status']);
        $this->assertSame('Worker 已完成任务：无等待测试', $stored['result']);
        $this->assertNotNull($stored['started_at']);
        $this->assertNotNull($stored['finished_at']);
    }
}
