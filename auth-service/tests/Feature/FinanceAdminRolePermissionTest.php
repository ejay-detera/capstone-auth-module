<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Department;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class FinanceAdminRolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seed(\Database\Seeders\DepartmentSeeder::class);
        $this->seed(\Database\Seeders\UserSeeder::class);
    }

    private function getFinanceAdminUserWithSession()
    {
        $user = User::where('email', 'finance-admin@example.com')->first();
        
        $sessionId = (string) Str::uuid();
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

    private function getITAdminUserWithSession()
    {
        $user = User::where('email', 'admin@example.com')->first();
        
        $sessionId = (string) Str::uuid();
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

    public function test_finance_admin_can_only_list_finance_roles()
    {
        [$user, $sessionId] = $this->getFinanceAdminUserWithSession();

        $response = $this->actingAs($user)
                         ->withHeader('X-Session-ID', $sessionId)
                         ->getJson('/api/admin/roles');

        $response->assertStatus(200);
        
        $roleNames = collect($response->json())->pluck('name')->toArray();

        // Must contain only the allowed department roles
        $this->assertContains('Finance Manager', $roleNames);
        $this->assertContains('Finance Employee', $roleNames);
        
        // Must NOT contain global admin/infrastructure roles
        $this->assertNotContains('IT Admin', $roleNames);
        $this->assertNotContains('Super Admin', $roleNames);
        $this->assertNotContains('Manager', $roleNames);
        $this->assertNotContains('Employee', $roleNames);
    }

    public function test_finance_admin_cannot_create_update_delete_roles()
    {
        [$user, $sessionId] = $this->getFinanceAdminUserWithSession();
        $financeManagerRole = Role::where('name', 'Finance Manager')->first();

        // Create -> forbidden
        $responseCreate = $this->actingAs($user)
                               ->withHeader('X-Session-ID', $sessionId)
                               ->postJson('/api/admin/roles', [
                                   'name' => 'Should Fail Role',
                                   'description' => 'Will fail'
                               ]);
        $responseCreate->assertStatus(403);

        // Update -> forbidden
        $responseUpdate = $this->actingAs($user)
                               ->withHeader('X-Session-ID', $sessionId)
                               ->putJson("/api/admin/roles/{$financeManagerRole->id}", [
                                   'name' => 'Finance Manager Updated',
                                   'description' => 'Will fail'
                               ]);
        $responseUpdate->assertStatus(403);

        // Delete -> forbidden
        $responseDelete = $this->actingAs($user)
                               ->withHeader('X-Session-ID', $sessionId)
                               ->deleteJson("/api/admin/roles/{$financeManagerRole->id}");
        $responseDelete->assertStatus(403);
    }

    public function test_finance_admin_can_view_finance_role_permissions_and_sync()
    {
        [$user, $sessionId] = $this->getFinanceAdminUserWithSession();
        $financeManagerRole = Role::where('name', 'Finance Manager')->first();
        $itAdminRole = Role::where('name', 'IT Admin')->first();

        // View allowed role permissions -> allowed
        $responseView = $this->actingAs($user)
                             ->withHeader('X-Session-ID', $sessionId)
                             ->getJson("/api/admin/roles/{$financeManagerRole->id}/permissions");
        $responseView->assertStatus(200);

        // View disallowed role permissions -> forbidden
        $responseViewDisallowed = $this->actingAs($user)
                                       ->withHeader('X-Session-ID', $sessionId)
                                       ->getJson("/api/admin/roles/{$itAdminRole->id}/permissions");
        $responseViewDisallowed->assertStatus(403);

        // Sync allowed role permissions -> allowed
        $permissions = Permission::limit(2)->pluck('id')->toArray();
        $responseSync = $this->actingAs($user)
                             ->withHeader('X-Session-ID', $sessionId)
                             ->postJson("/api/admin/roles/{$financeManagerRole->id}/permissions", [
                                 'permissions' => $permissions
                             ]);
        $responseSync->assertStatus(200);

        // Sync disallowed role permissions -> forbidden
        $responseSyncDisallowed = $this->actingAs($user)
                                       ->withHeader('X-Session-ID', $sessionId)
                                       ->postJson("/api/admin/roles/{$itAdminRole->id}/permissions", [
                                           'permissions' => $permissions
                                       ]);
        $responseSyncDisallowed->assertStatus(403);
    }

    public function test_finance_admin_can_only_see_finance_users_for_finance_roles()
    {
        [$user, $sessionId] = $this->getFinanceAdminUserWithSession();
        $financeManagerRole = Role::where('name', 'Finance Manager')->first();
        
        $financeDept = Department::where('name', 'Finance')->first();
        $opsDept = Department::where('name', 'Operations')->first();

        // User in Finance department with Finance Manager role
        $financeUser = User::create(['email' => 'finance-user-test@example.com', 'is_active' => true]);
        UserProfile::create([
            'user_id' => $financeUser->id,
            'role_id' => $financeManagerRole->id,
            'department_id' => $financeDept->id,
            'first_name' => 'Finance',
            'last_name' => 'User'
        ]);

        // User in Operations department with Finance Manager role (simulated cross-boundary user)
        $opsUser = User::create(['email' => 'ops-user-test@example.com', 'is_active' => true]);
        UserProfile::create([
            'user_id' => $opsUser->id,
            'role_id' => $financeManagerRole->id,
            'department_id' => $opsDept->id,
            'first_name' => 'Ops',
            'last_name' => 'User'
        ]);

        $response = $this->actingAs($user)
                         ->withHeader('X-Session-ID', $sessionId)
                         ->getJson("/api/admin/roles/{$financeManagerRole->id}/users");

        $response->assertStatus(200);
        
        $emails = collect($response->json()['data'])->pluck('email')->toArray();
        $this->assertContains('finance-user-test@example.com', $emails);
        $this->assertNotContains('ops-user-test@example.com', $emails);
    }

    public function test_finance_admin_can_only_assign_finance_roles_to_finance_users()
    {
        [$user, $sessionId] = $this->getFinanceAdminUserWithSession();
        $financeManagerRole = Role::where('name', 'Finance Manager')->first();
        $itAdminRole = Role::where('name', 'IT Admin')->first();

        $financeDept = Department::where('name', 'Finance')->first();
        $opsDept = Department::where('name', 'Operations')->first();

        // User in Finance department
        $financeUser = User::create(['email' => 'finance-user-assign@sbsi.com', 'is_active' => true]);
        UserProfile::create([
            'user_id' => $financeUser->id,
            'role_id' => Role::where('name', 'Finance Employee')->first()->id,
            'department_id' => $financeDept->id,
            'first_name' => 'Finance',
            'last_name' => 'User'
        ]);

        // User in Operations department
        $opsUser = User::create(['email' => 'ops-user-assign@sbsi.com', 'is_active' => true]);
        UserProfile::create([
            'user_id' => $opsUser->id,
            'role_id' => Role::where('name', 'Employee')->first()->id,
            'department_id' => $opsDept->id,
            'first_name' => 'Ops',
            'last_name' => 'User'
        ]);

        // 1. Assign allowed role to Finance user -> allowed
        $response1 = $this->actingAs($user)
                          ->withHeader('X-Session-ID', $sessionId)
                          ->patchJson("/api/admin/users/{$financeUser->id}/role", [
                              'role_id' => $financeManagerRole->id
                          ]);
        $response1->assertStatus(200);
        $this->assertEquals($financeManagerRole->id, $financeUser->fresh()->profile->role_id);

        // 2. Assign disallowed role to Finance user -> forbidden
        $response2 = $this->actingAs($user)
                          ->withHeader('X-Session-ID', $sessionId)
                          ->patchJson("/api/admin/users/{$financeUser->id}/role", [
                              'role_id' => $itAdminRole->id
                          ]);
        $response2->assertStatus(403);

        // 3. Assign allowed role to Operations user -> forbidden
        $response3 = $this->actingAs($user)
                          ->withHeader('X-Session-ID', $sessionId)
                          ->patchJson("/api/admin/users/{$opsUser->id}/role", [
                              'role_id' => $financeManagerRole->id
                          ]);
        $response3->assertStatus(403);
    }

    public function test_finance_admin_can_view_and_sync_permission_roles_for_finance_roles()
    {
        [$user, $sessionId] = $this->getFinanceAdminUserWithSession();
        $permission = Permission::where('slug', 'crms.templates.use')->first();

        // 1. View roles for permission -> only shows Finance Manager and Finance Employee
        $responseView = $this->actingAs($user)
                             ->withHeader('X-Session-ID', $sessionId)
                             ->getJson("/api/admin/permissions/{$permission->id}/roles");
        $responseView->assertStatus(200);
        
        $roleNames = collect($responseView->json())->pluck('name')->toArray();
        foreach ($roleNames as $name) {
            $this->assertContains($name, ['Finance Manager', 'Finance Employee']);
        }

        // 2. Sync allowed roles -> allowed
        $financeManagerRole = Role::where('name', 'Finance Manager')->first();
        $financeEmployeeRole = Role::where('name', 'Finance Employee')->first();

        // Must preserve existing disallowed roles
        $currentRoleIds = $permission->roles()->pluck('roles.id')->toArray();
        $allowedRoles = Role::whereIn('name', ['Finance Manager', 'Finance Employee'])->pluck('id')->toArray();
        $disallowedRoles = array_diff($currentRoleIds, $allowedRoles);

        $responseSync = $this->actingAs($user)
                             ->withHeader('X-Session-ID', $sessionId)
                             ->postJson("/api/admin/permissions/{$permission->id}/roles", [
                                 'role_ids' => array_merge($disallowedRoles, [$financeManagerRole->id, $financeEmployeeRole->id])
                             ]);
        $responseSync->assertStatus(200);

        // 3. Sync including disallowed roles (trying to add a new disallowed one) -> forbidden
        $itAdminRole = Role::where('name', 'IT Admin')->first();
        $responseSyncDisallowed = $this->actingAs($user)
                                       ->withHeader('X-Session-ID', $sessionId)
                                       ->postJson("/api/admin/permissions/{$permission->id}/roles", [
                                           'role_ids' => array_merge($disallowedRoles, [$financeManagerRole->id, $itAdminRole->id])
                                       ]);
        $responseSyncDisallowed->assertStatus(403);
    }

    public function test_finance_admin_can_only_create_user_with_finance_roles_and_finance_dept()
    {
        [$user, $sessionId] = $this->getFinanceAdminUserWithSession();
        $financeManagerRole = Role::where('name', 'Finance Manager')->first();
        $globalManagerRole = Role::where('name', 'Manager')->first();
        
        $financeDept = Department::where('name', 'Finance')->first();
        $opsDept = Department::where('name', 'Operations')->first();

        // 1. Create user with Finance dept & Finance Manager role -> allowed
        $response1 = $this->actingAs($user)
                          ->withHeader('X-Session-ID', $sessionId)
                          ->postJson('/api/admin/users', [
                              'email' => 'new-finance-mgr@sbsi.com',
                              'first_name' => 'New',
                              'last_name' => 'Manager',
                              'role_id' => $financeManagerRole->id,
                              'department_id' => $financeDept->id,
                          ]);
        $response1->assertStatus(201);

        // 2. Create user with Finance dept & Global Manager role -> forbidden
        $response2 = $this->actingAs($user)
                          ->withHeader('X-Session-ID', $sessionId)
                          ->postJson('/api/admin/users', [
                              'email' => 'new-global-mgr@sbsi.com',
                              'first_name' => 'New',
                              'last_name' => 'Manager',
                              'role_id' => $globalManagerRole->id,
                              'department_id' => $financeDept->id,
                          ]);
        $response2->assertStatus(403);

        // 3. Create user with Operations dept & Finance Manager role -> forbidden
        $response3 = $this->actingAs($user)
                          ->withHeader('X-Session-ID', $sessionId)
                          ->postJson('/api/admin/users', [
                              'email' => 'new-ops-mgr@sbsi.com',
                              'first_name' => 'New',
                              'last_name' => 'Manager',
                              'role_id' => $financeManagerRole->id,
                              'department_id' => $opsDept->id,
                          ]);
        $response3->assertStatus(403);
    }
}
