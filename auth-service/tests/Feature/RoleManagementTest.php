<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seed(\Database\Seeders\DepartmentSeeder::class);
        $this->seed(\Database\Seeders\UserSeeder::class);
    }

    private function getAdminUserWithSession()
    {
        $user = User::where('email', 'admin@example.com')->first();
        
        $sessionId = (string) \Illuminate\Support\Str::uuid();
        DB::table('user_sessions')->insert([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Testing',
            'last_active_at' => now(),
            'is_active' => true,
            'created_at' => now(),
        ]);

        return [$user, $sessionId];
    }

    public function test_can_list_roles()
    {
        [$user, $sessionId] = $this->getAdminUserWithSession();

        $response = $this->actingAs($user)
                         ->withHeader('X-Session-ID', $sessionId)
                         ->getJson('/api/admin/roles');

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'IT Admin'])
                 ->assertJsonFragment(['name' => 'Sales']);
    }

    public function test_can_create_role()
    {
        [$user, $sessionId] = $this->getAdminUserWithSession();

        $response = $this->actingAs($user)
                         ->withHeader('X-Session-ID', $sessionId)
                         ->postJson('/api/admin/roles', [
                             'name' => 'Custom Role',
                             'description' => 'A custom role description'
                         ]);

        $response->assertStatus(201)
                 ->assertJsonFragment(['name' => 'Custom Role']);
                 
        $this->assertDatabaseHas('roles', ['name' => 'Custom Role']);
        
        // Check audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'ROLE_CREATED',
            'description' => 'Created role: Custom Role'
        ]);
    }

    public function test_can_update_role()
    {
        [$user, $sessionId] = $this->getAdminUserWithSession();
        $role = Role::create(['name' => 'Old Name', 'description' => 'Old desc']);

        $response = $this->actingAs($user)
                         ->withHeader('X-Session-ID', $sessionId)
                         ->putJson("/api/admin/roles/{$role->id}", [
                             'name' => 'New Name',
                             'description' => 'Updated desc'
                         ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'New Name']);
    }

    public function test_can_delete_role_without_users()
    {
        [$user, $sessionId] = $this->getAdminUserWithSession();
        $role = Role::create(['name' => 'To Be Deleted', 'description' => 'To be deleted desc']);

        $response = $this->actingAs($user)
                         ->withHeader('X-Session-ID', $sessionId)
                         ->deleteJson("/api/admin/roles/{$role->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_cannot_delete_role_with_users()
    {
        [$user, $sessionId] = $this->getAdminUserWithSession();
        $role = Role::where('name', 'IT Admin')->first();

        $response = $this->actingAs($user)
                         ->withHeader('X-Session-ID', $sessionId)
                         ->deleteJson("/api/admin/roles/{$role->id}");

        $response->assertStatus(409); // Conflict
        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    public function test_can_sync_permissions_and_invalidates_cache()
    {
        [$user, $sessionId] = $this->getAdminUserWithSession();
        $role = Role::where('name', 'Sales')->first();
        
        $permission1 = Permission::where('slug', 'crms.roles.manage')->first();
        $permission2 = Permission::where('slug', 'crms.templates.manage')->first();

        // Seed some cache to ensure it's cleared
        $salesUser = User::where('email', 'sales@example.com')->first();
        Cache::store('database')->put("permissions:user:{$salesUser->id}", ['dummy'], 300);

        $response = $this->actingAs($user)
                         ->withHeader('X-Session-ID', $sessionId)
                         ->postJson("/api/admin/roles/{$role->id}/permissions", [
                             'permissions' => [$permission1->id, $permission2->id]
                         ]);

        $response->assertStatus(200);
        
        // Assert sync
        $this->assertTrue($role->permissions->contains($permission1->id));
        $this->assertTrue($role->permissions->contains($permission2->id));
        
        // Assert cache invalidation
        $this->assertNull(Cache::store('database')->get("permissions:user:{$salesUser->id}"));
    }
}
