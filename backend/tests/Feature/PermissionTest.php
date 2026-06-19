<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_permission_is_forbidden(): void
    {
        Sanctum::actingAs(User::factory()->create(['username' => 'normal', 'enabled' => true]));
        $this->getJson('/api/v1/users')->assertForbidden();
    }

    public function test_super_admin_can_access_protected_resource(): void
    {
        Sanctum::actingAs(User::factory()->create(['username' => 'root', 'enabled' => true, 'is_super_admin' => true]));
        $this->getJson('/api/v1/users')->assertOk();
    }
}

