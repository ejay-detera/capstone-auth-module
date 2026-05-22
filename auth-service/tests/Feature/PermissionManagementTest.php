<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PermissionManagementTest extends TestCase
{
    use RefreshDatabase;

    // protected $seed = true;

    private function getAdminHeaders()
    {
        $admin = User::factory()->create(['is_active' => true]);
        $role = Role::firstOrCreate(['name' => 'IT Admin']);
        $permManageRoles = Permission::firstOrCreate(
            ['slug' => 'manage-roles'],
            ['name' => 'Manage Roles']
        );
        $permManageUsers = Permission::firstOrCreate(
            ['slug' => 'manage-users'],
            ['name' => 'Manage Users']
        );
        
        $role->permissions()->syncWithoutDetaching([$permManageRoles->id, $permManageUsers->id]);

        UserProfile::updateOrCreate(
            ['user_id' => $admin->id],
            ['role_id' => $role->id]
        );

        $accessToken = $admin->createToken('auth_token')->plainTextToken;
        $sessionId = (string) Str::uuid();

        DB::table('user_sessions')->insert([
            'user_id' => $admin->id,
            'session_id' => $sessionId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'TestAgent',
            'last_active_at' => now(),
            'is_active' => true,
            'created_at' => now(),
        ]);

        return [
            'Authorization' => 'Bearer ' . $accessToken,
            'X-Session-ID' => $sessionId,
            'Accept' => 'application/json'
        ];
    }

    public function test_can_crud_permissions()
    {
        $headers = $this->getAdminHeaders();

        // Create
        $response = $this->postJson('/api/admin/permissions', [
            'name' => 'New Permission',
            'slug' => 'new-permission',
            'description' => 'Test description'
        ], $headers);

        $response->assertStatus(201);
        $permissionId = $response->json('id');

        // List
        $response = $this->getJson('/api/admin/permissions', $headers);
        $response->assertStatus(200)->assertJsonFragment(['slug' => 'new-permission']);

        // Show
        $response = $this->getJson("/api/admin/permissions/{$permissionId}", $headers);
        $response->assertStatus(200)->assertJsonFragment(['slug' => 'new-permission']);

        // Update
        $response = $this->putJson("/api/admin/permissions/{$permissionId}", [
            'name' => 'Updated Permission',
            'slug' => 'updated-permission',
            'description' => 'Updated description'
        ], $headers);

        $response->assertStatus(200);
        $this->assertDatabaseHas('permissions', ['slug' => 'updated-permission']);

        // Delete
        $response = $this->deleteJson("/api/admin/permissions/{$permissionId}", [], $headers);
        $response->assertStatus(200);
        $this->assertDatabaseMissing('permissions', ['id' => $permissionId]);
    }

    public function test_permission_resolution_and_caching()
    {
        $admin = User::factory()->create(['is_active' => true]);
        $role = Role::create(['name' => 'Test Role']);
        $permission = Permission::create([
            'name' => 'Test Perm',
            'slug' => 'test-perm'
        ]);
        $role->permissions()->syncWithoutDetaching([$permission->id]);

        UserProfile::create([
            'user_id' => $admin->id,
            'role_id' => $role->id
        ]);

        $headers = $this->getAdminHeaders(); // Different admin to perform the request

        // First call - should populate cache
        $response = $this->getJson("/api/users/{$admin->id}/permissions", $headers);
        $response->assertStatus(200)->assertJson(['test-perm']);

        $this->assertTrue(Cache::store('database')->has("permissions:user:{$admin->id}"));
        $this->assertEquals(['test-perm'], Cache::store('database')->get("permissions:user:{$admin->id}"));
    }

    public function test_cache_invalidation_on_role_sync()
    {
        $user = User::factory()->create(['is_active' => true]);
        $role = Role::create(['name' => 'Sync Role']);
        $perm1 = Permission::create(['name' => 'Perm 1', 'slug' => 'perm-1']);
        $role->permissions()->syncWithoutDetaching([$perm1->id]);

        UserProfile::create([
            'user_id' => $user->id,
            'role_id' => $role->id
        ]);

        $headers = $this->getAdminHeaders();

        // Populate cache
        $this->getJson("/api/users/{$user->id}/permissions", $headers);
        $this->assertTrue(Cache::store('database')->has("permissions:user:{$user->id}"));

        // Sync new permissions to role
        $perm2 = Permission::create(['name' => 'Perm 2', 'slug' => 'perm-2']);
        $this->postJson("/api/admin/roles/{$role->id}/permissions", [
            'permissions' => [$perm2->id]
        ], $headers);

        // Cache should be invalidated
        $this->assertFalse(Cache::store('database')->has("permissions:user:{$user->id}"));

        // New call should have updated permissions
        $response = $this->getJson("/api/users/{$user->id}/permissions", $headers);
        $response->assertStatus(200)->assertJson(['perm-2']);
    }

    public function test_cache_invalidation_on_permission_update()
    {
        $user = User::factory()->create(['is_active' => true]);
        $role = Role::create(['name' => 'Update Role']);
        $perm = Permission::create(['name' => 'Old Name', 'slug' => 'old-slug']);
        $role->permissions()->syncWithoutDetaching([$perm->id]);

        UserProfile::create([
            'user_id' => $user->id,
            'role_id' => $role->id
        ]);

        $headers = $this->getAdminHeaders();

        // Populate cache
        $this->getJson("/api/users/{$user->id}/permissions", $headers);
        $this->assertTrue(Cache::store('database')->has("permissions:user:{$user->id}"));

        // Update permission
        $this->putJson("/api/admin/permissions/{$perm->id}", [
            'name' => 'New Name',
            'slug' => 'new-slug'
        ], $headers);

        // Cache should be invalidated
        $this->assertFalse(Cache::store('database')->has("permissions:user:{$user->id}"));
    }

    public function test_cache_invalidation_on_permission_roles_sync()
    {
        $user = User::factory()->create(['is_active' => true]);
        $role = Role::create(['name' => 'Sync Roles Role']);
        $perm = Permission::create(['name' => 'Perm X', 'slug' => 'perm-x']);
        // No assignment yet

        UserProfile::create([
            'user_id' => $user->id,
            'role_id' => $role->id
        ]);

        $headers = $this->getAdminHeaders();

        // Populate cache (should be empty array)
        $this->getJson("/api/users/{$user->id}/permissions", $headers);
        $this->assertTrue(Cache::store('database')->has("permissions:user:{$user->id}"));

        // Assign permission to role from the permission side
        $this->postJson("/api/admin/permissions/{$perm->id}/roles", [
            'role_ids' => [$role->id]
        ], $headers);

        // Cache should be invalidated
        $this->assertFalse(Cache::store('database')->has("permissions:user:{$user->id}"));

        // New call should have the permission
        $response = $this->getJson("/api/users/{$user->id}/permissions", $headers);
        $response->assertStatus(200)->assertJson(['perm-x']);
    }
}
