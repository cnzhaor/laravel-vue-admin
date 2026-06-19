<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_read_profile(): void
    {
        $this->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_enabled_user_can_login_and_read_profile(): void
    {
        $user = User::factory()->create([
            'username' => 'tester',
            'password' => 'Password123!',
            'enabled' => true,
        ]);

        $this->withSession(['_token' => 'test-token'])
            ->withHeader('X-CSRF-TOKEN', 'test-token')
            ->postJson('/api/v1/login', ['username' => 'tester', 'password' => 'Password123!'])
            ->assertOk()->assertJsonPath('data.user.id', $user->id);
        $this->getJson('/api/v1/me')->assertOk();
    }
}
