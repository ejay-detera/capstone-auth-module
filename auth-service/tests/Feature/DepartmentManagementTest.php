<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\UserProfile;
use App\Models\Permission;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DepartmentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $regularUser;
    protected $manageDeptsPermission;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup IT Admin Role and Permission
        $itAdminRole = Role::firstOrCreate(['name' => 'IT Admin'], ['description' => 'IT Admin Role']);
        $this->manageDeptsPermission = Permission::firstOrCreate(
            ['slug' => 'manage-departments'],
            ['name' => 'Manage Departments']
        );
        
        DB::table('role_permission')->insertOrIgnore([
            'role_id' => $itAdminRole->id,
            'permission_id' => $this->manageDeptsPermission->id
        ]);

        $this->adminUser = User::factory()->create();
        UserProfile::updateOrCreate(
            ['user_id' => $this->adminUser->id],
            [
                'first_name' => 'Admin',
                'last_name' => 'User',
                'role_id' => $itAdminRole->id
            ]
        );
        $this->adminUser->refresh();

        $employeeRole = Role::firstOrCreate(['name' => 'Employee'], ['description' => 'Employee Role']);
        $this->regularUser = User::factory()->create();
        UserProfile::updateOrCreate(
            ['user_id' => $this->regularUser->id],
            [
                'first_name' => 'Regular',
                'last_name' => 'User',
                'role_id' => $employeeRole->id
            ]
        );

        // Setup Sessions
        $this->setupSessionFor($this->adminUser);
        $this->setupSessionFor($this->regularUser);
    }

    protected function setupSessionFor($user)
    {
        $sessionId = \Illuminate\Support\Str::uuid()->toString();
        DB::table('user_sessions')->insert([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Testing',
            'is_active' => true,
            'last_active_at' => now(),
            'created_at' => now(),
        ]);
        
        $user->withSessionId = $sessionId;
    }

    protected function actingAsWithSession($user)
    {
        return $this->actingAs($user)
                    ->withHeader('X-Session-ID', $user->withSessionId);
    }

    public function test_admin_can_list_departments()
    {
        Department::create(['name' => 'Engineering']);
        
        $response = $this->actingAsWithSession($this->adminUser)
                         ->getJson('/api/admin/departments');

        $response->assertStatus(200)
                 ->assertJsonCount(1);
    }

    public function test_non_admin_cannot_list_departments()
    {
        $response = $this->actingAsWithSession($this->regularUser)
                         ->getJson('/api/admin/departments');

        $response->assertStatus(403);
    }

    public function test_admin_can_create_department()
    {
        $response = $this->actingAsWithSession($this->adminUser)
                         ->postJson('/api/admin/departments', [
                             'name' => 'Marketing',
                             'description' => 'Marketing Dept'
                         ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('departments', ['name' => 'Marketing']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'DEPARTMENT_CREATED']);
    }

    public function test_admin_can_update_department()
    {
        $dept = Department::create(['name' => 'Sales']);
        
        $response = $this->actingAsWithSession($this->adminUser)
                         ->putJson("/api/admin/departments/{$dept->id}", [
                             'name' => 'Business Development',
                             'description' => 'New Description'
                         ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('departments', ['name' => 'Business Development']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'DEPARTMENT_UPDATED']);
    }

    public function test_admin_cannot_delete_department_with_users()
    {
        $dept = Department::create(['name' => 'HR']);
        $this->regularUser->profile->update(['department_id' => $dept->id]);
        
        $response = $this->actingAsWithSession($this->adminUser)
                         ->deleteJson("/api/admin/departments/{$dept->id}");

        $response->assertStatus(409)
                 ->assertJsonFragment(['message' => 'Cannot delete department with assigned users.']);
    }

    public function test_admin_can_delete_department_without_users()
    {
        $dept = Department::create(['name' => 'Temporary']);
        
        $response = $this->actingAsWithSession($this->adminUser)
                         ->deleteJson("/api/admin/departments/{$dept->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('departments', ['id' => $dept->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'DEPARTMENT_DELETED']);
    }

    public function test_admin_can_assign_department_to_user()
    {
        $dept = Department::create(['name' => 'Product']);
        $targetUser = $this->regularUser;

        $response = $this->actingAsWithSession($this->adminUser)
                         ->patchJson("/api/admin/users/{$targetUser->id}/department", [
                             'department_id' => $dept->id
                         ]);

        $response->assertStatus(200);
        $this->assertEquals($dept->id, $targetUser->fresh()->profile->department_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'USER_DEPARTMENT_CHANGED']);
    }

    public function test_admin_can_list_users_for_department()
    {
        $dept = Department::create(['name' => 'Support']);
        $this->regularUser->profile->update(['department_id' => $dept->id]);
        
        $response = $this->actingAsWithSession($this->adminUser)
                         ->getJson("/api/admin/departments/{$dept->id}/users");

        $response->assertStatus(200)
                 ->assertJsonStructure(['data', 'current_page', 'last_page']);
    }
}
