<?php

namespace Tests\Feature;

use App\Jobs\ProcessQueueDemo;
use App\Models\User;
use App\Services\QueueDemoService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
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
            ->assertJsonPath('data.status', 'queued')
            ->assertJsonPath('data.delay_seconds', 3)
            ->assertJsonStructure(['data' => ['available_at']]);

        $taskId = $response->json('data.id');
        $this->assertTrue(
            CarbonImmutable::parse($response->json('data.created_at'))
                ->addSeconds(3)
                ->equalTo(CarbonImmutable::parse($response->json('data.available_at'))),
        );

        Queue::assertPushed(
            ProcessQueueDemo::class,
            fn (ProcessQueueDemo $job) => $job->taskId === $taskId && $job->delay === 3,
        );
        $this->getJson("/api/v1/queue-demo/jobs/{$taskId}")
            ->assertOk()
            ->assertJsonPath('data.message', '演示任务');
    }

    public function test_demo_job_delay_must_be_between_one_and_ten_seconds(): void
    {
        Queue::fake();
        Sanctum::actingAs(User::factory()->create(['username' => 'queue-demo-validator']));

        $this->postJson('/api/v1/queue-demo/jobs', [
            'message' => '延迟过短',
            'delay_seconds' => 0,
        ])->assertUnprocessable()->assertJsonStructure([
            'data' => ['errors' => ['delay_seconds']],
        ]);

        $this->postJson('/api/v1/queue-demo/jobs', [
            'message' => '延迟过长',
            'delay_seconds' => 11,
        ])->assertUnprocessable()->assertJsonStructure([
            'data' => ['errors' => ['delay_seconds']],
        ]);

        Queue::assertNothingPushed();
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
        $task = app(QueueDemoService::class)->create(1, '延迟任务测试', 3);

        $queued = app(QueueDemoService::class)->find($task['id']);

        $this->assertNotNull($queued);
        $this->assertSame('queued', $queued['status']);
        $this->assertNull($queued['started_at']);
        $this->assertNotNull($queued['available_at']);

        (new ProcessQueueDemo($task['id']))->handle(app(QueueDemoService::class));

        $stored = app(QueueDemoService::class)->find($task['id']);

        $this->assertNotNull($stored);
        $this->assertSame('completed', $stored['status']);
        $this->assertSame('Worker 已执行延迟任务：延迟任务测试', $stored['result']);
        $this->assertNotNull($stored['started_at']);
        $this->assertNotNull($stored['finished_at']);

        (new ProcessQueueDemo($task['id']))->handle(app(QueueDemoService::class));
        (new ProcessQueueDemo($task['id']))->failed(new RuntimeException('不应覆盖已完成状态'));

        $this->assertSame($stored, app(QueueDemoService::class)->find($task['id']));
    }

    public function test_demo_job_failure_hides_exception_details_and_preserves_terminal_state(): void
    {
        $task = app(QueueDemoService::class)->create(1, '失败任务测试', 3);
        $job = new ProcessQueueDemo($task['id']);

        $job->failed(new RuntimeException('redis://admin:secret@internal-host'));

        $failed = app(QueueDemoService::class)->find($task['id']);

        $this->assertNotNull($failed);
        $this->assertSame('failed', $failed['status']);
        $this->assertSame('任务执行失败，请联系管理员', $failed['result']);
        $this->assertStringNotContainsString('secret', (string) $failed['result']);

        $job->handle(app(QueueDemoService::class));

        $this->assertSame($failed, app(QueueDemoService::class)->find($task['id']));
    }
}
