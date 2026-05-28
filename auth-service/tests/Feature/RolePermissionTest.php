<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function getAdminTokens()
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['is_active' => true]
        );

        $role = Role::firstOrCreate(['name' => 'IT Admin']);

        $permission = Permission::firstOrCreate(
            ['slug' => 'manage-roles'],
            ['name' => 'Manage Roles']
        );

        if (!$role->permissions()->where('permissions.id', $permission->id)->exists()) {
            $role->permissions()->attach($permission);
        }

        UserProfile::updateOrCreate(
            ['user_id' => $admin->id],
            ['role_id' => $role->id]
        );

        $accessToken = $admin->createToken('auth_token')->plainTextToken;
        $sessionId   = (string) Str::uuid();

        DB::table('user_sessions')->insert([
            'user_id'        => $admin->id,
            'session_id'     => $sessionId,
            'ip_address'     => '127.0.0.1',
            'user_agent'     => 'TestAgent',
            'last_active_at' => now(),
            'is_active'      => true,
            'created_at'     => now(),
        ]);

        return ['access_token' => $accessToken, 'session_id' => $sessionId, 'user' => $admin];
    }

    public function test_admin_can_list_permissions()
    {
        $tokens = $this->getAdminTokens();

        $response = $this->call('GET', '/api/admin/permissions', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $tokens['access_token'],
            'HTTP_X_SESSION_ID' => $tokens['session_id']
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['slug' => 'manage-roles']);
    }

    public function test_admin_can_get_role_permissions()
    {
        $tokens = $this->getAdminTokens();
        $role = Role::first();

        $response = $this->call('GET', "/api/admin/roles/{$role->id}/permissions", [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $tokens['access_token'],
            'HTTP_X_SESSION_ID' => $tokens['session_id']
        ]);

        $response->assertStatus(200);
        $this->assertIsArray($response->json());
    }

    public function test_admin_can_sync_role_permissions()
    {
        $tokens = $this->getAdminTokens();
        $role = Role::create(['name' => 'Test Role']);
        $permissions = Permission::limit(2)->pluck('id')->toArray();

        $response = $this->call('POST', "/api/admin/roles/{$role->id}/permissions", [
            'permissions' => $permissions
        ], [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $tokens['access_token'],
            'HTTP_X_SESSION_ID' => $tokens['session_id']
        ]);

        $response->assertStatus(200);
        $this->assertEquals(count($permissions), $role->permissions()->count());
        
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'ROLE_PERMISSIONS_UPDATED',
            'actor_id' => $tokens['user']->id
        ]);
    }
}
