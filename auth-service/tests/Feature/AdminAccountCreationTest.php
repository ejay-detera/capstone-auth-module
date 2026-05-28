<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Department;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminAccountCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seed(\Database\Seeders\DepartmentSeeder::class);
        $this->seed(\Database\Seeders\UserSeeder::class);
    }

    private function getUserWithSession($email)
    {
        $user = User::where('email', $email)->first();
        
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

    public function test_it_admin_can_create_account_for_any_department()
    {
        [$user, $sessionId] = $this->getUserWithSession('admin@example.com'); // IT Admin
        $itDept = Department::where('name', 'IT')->first();
        $role = Role::where('name', 'Manager')->first();

        $response = $this->actingAs($user)
                         ->withHeader('X-Session-ID', $sessionId)
                         ->postJson('/api/admin/users', [
                             'first_name' => 'New',
                             'last_name' => 'ITUser',
                             'email' => 'new.it@sbsi.com',
                             'role_id' => $role->id,
                             'department_id' => $itDept->id
                         ]);

        $response->assertStatus(201)
                 ->assertJsonFragment(['email' => 'new.it@sbsi.com']);
    }

    public function test_finance_admin_can_create_account_for_finance_department()
    {
        [$user, $sessionId] = $this->getUserWithSession('finance-admin@example.com');
        $financeDept = Department::where('name', 'Finance')->first();
        $role = Role::where('name', 'Finance Manager')->first();

        $response = $this->actingAs($user)
                         ->withHeader('X-Session-ID', $sessionId)
                         ->postJson('/api/admin/users', [
                             'first_name' => 'New',
                             'last_name' => 'FinanceUser',
                             'email' => 'new.finance@sbsi.com',
                             'role_id' => $role->id,
                             'department_id' => $financeDept->id
                         ]);

        $response->dump()->assertStatus(201)
                 ->assertJsonFragment(['email' => 'new.finance@sbsi.com']);
    }

    public function test_finance_admin_cannot_create_account_for_other_departments()
    {
        [$user, $sessionId] = $this->getUserWithSession('finance-admin@example.com');
        $itDept = Department::where('name', 'IT')->first();
        $role = Role::where('name', 'Finance Manager')->first();

        $response = $this->actingAs($user)
                         ->withHeader('X-Session-ID', $sessionId)
                         ->postJson('/api/admin/users', [
                             'first_name' => 'New',
                             'last_name' => 'ITUser',
                             'email' => 'new.it2@sbsi.com',
                             'role_id' => $role->id,
                             'department_id' => $itDept->id
                         ]);

        $response->assertStatus(403)
                 ->assertJsonFragment(['message' => 'You can only create accounts for the Finance department.']);
    }

    public function test_finance_admin_cannot_assign_admin_role()
    {
        [$user, $sessionId] = $this->getUserWithSession('finance-admin@example.com');
        $financeDept = Department::where('name', 'Finance')->first();
        $role = Role::where('name', 'Admin')->first();

        $response = $this->actingAs($user)
                         ->withHeader('X-Session-ID', $sessionId)
                         ->postJson('/api/admin/users', [
                             'first_name' => 'New',
                             'last_name' => 'FinanceUser',
                             'email' => 'new.finance2@sbsi.com',
                             'role_id' => $role->id,
                             'department_id' => $financeDept->id
                         ]);

        $response->assertStatus(403)
                 ->assertJsonFragment(['message' => 'You are only authorized to assign Finance Manager or Finance Employee roles.']);
    }
}
