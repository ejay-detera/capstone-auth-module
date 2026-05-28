<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\Role;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\WelcomeEmail;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }
    //public function test_debug_permissions()
    //{
    //    dd(\App\Models\Permission::all()->toArray());
    //}
    private function getAdminTokens()
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['is_active' => true]
        );

        $role = Role::firstOrCreate(['name' => 'IT Admin']); // always safe

        // Use firstOrCreate — won't blow up if the seeder already inserted it
        $permission = \App\Models\Permission::firstOrCreate(
            ['slug' => 'manage-users'],
            ['name' => 'Manage Users']
        );

        // Only attach if not already attached
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


    public function test_admin_can_create_user()
    {
        Mail::fake();
        $tokens = $this->getAdminTokens();

        $role = Role::firstOrCreate(['name' => 'Employee']);
        $dept = Department::firstOrCreate(['name' => 'IT']);

        $response = $this->call('POST', '/api/admin/users', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@sbsi.com',
            'role_id' => $role->id,
            'department_id' => $dept->id,
        ], [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $tokens['access_token'],
            'HTTP_X_SESSION_ID' => $tokens['session_id']
        ]);
        $admin = $tokens['user']->fresh(['profile.role']);
        dump([
            'user_id'    => $admin->id,
            'profile'    => $admin->profile?->toArray(),
            'role_id'    => $admin->profile?->role_id,
            'perms'      => $admin->profile?->role?->permissions?->pluck('slug'),
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'john@sbsi.com']);
        $this->assertDatabaseHas('user_profiles', ['first_name' => 'John']);
        
        $user = User::where('email', 'john@sbsi.com')->first();
        $this->assertDatabaseHas('user_credentials', [
            'user_id' => $user->id,
            'must_change_password' => 1
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'ACCOUNT_CREATED',
            'actor_id' => $tokens['user']->id
        ]);

        Mail::assertQueued(WelcomeEmail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_admin_cannot_create_duplicate_email()
    {
        $tokens = $this->getAdminTokens();
        User::create(['email' => 'ex@example.com', 'is_active' => true]);

        $role = Role::firstOrCreate(['name' => 'Employee']);
        $dept = Department::firstOrCreate(['name' => 'IT']);

        $response = $this->call('POST', '/api/admin/users', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'ex@example.com',
            'role_id' => $role->id,
            'department_id' => $dept->id,
        ], [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $tokens['access_token'],
            'HTTP_X_SESSION_ID' => $tokens['session_id']
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_unauthorized_user_cannot_access()
    {
        // Normal user without IT Admin role
        $user = User::create(['email' => 'norm@example.com', 'is_active' => true]);
        UserProfile::create(['user_id' => $user->id]);

        $accessToken = $user->createToken('auth_token')->plainTextToken;
        $sessionId = (string) Str::uuid();

        DB::table('user_sessions')->insert([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'is_active' => true,
            'created_at' => now(),
        ]);

        $response = $this->call('GET', '/api/admin/users', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $accessToken,
            'HTTP_X_SESSION_ID' => $sessionId
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_toggle_user_status_with_valid_password()
    {
        $tokens = $this->getAdminTokens();
        $admin = $tokens['user'];

        // Seed admin password
        DB::table('user_credentials')->updateOrInsert(
            ['user_id' => $admin->id],
            [
                'password_hash' => \Illuminate\Support\Facades\Hash::make('AdminPassword123!'),
                'must_change_password' => false,
                'created_at' => now(),
                'updated_at' => now()
            ]
        );

        // Create a user to toggle
        $user = User::create(['email' => 'employee@sbsi.com', 'is_active' => true]);

        // Insert session and refresh token for the user to verify revocation
        DB::table('user_sessions')->insert([
            'user_id' => $user->id,
            'session_id' => (string) Str::uuid(),
            'is_active' => true,
            'created_at' => now()
        ]);
        DB::table('refresh_tokens')->insert([
            'user_id' => $user->id,
            'token_hash' => 'dummy_hash',
            'expires_at' => now()->addDays(30),
            'created_at' => now(),
            'is_revoked' => false
        ]);

        // Toggle from Active to Inactive
        $response = $this->call('PATCH', "/api/admin/users/{$user->id}/status", [
            'password' => 'AdminPassword123!'
        ], [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $tokens['access_token'],
            'HTTP_X_SESSION_ID' => $tokens['session_id']
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_active' => false
        ]);

        // Verify sessions and refresh tokens are revoked
        $this->assertDatabaseMissing('user_sessions', [
            'user_id' => $user->id,
            'is_active' => true
        ]);
        $this->assertDatabaseHas('refresh_tokens', [
            'user_id' => $user->id,
            'is_revoked' => true
        ]);

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'USER_DEACTIVATED',
            'actor_id' => $admin->id
        ]);

        // Toggle back from Inactive to Active
        $response2 = $this->call('PATCH', "/api/admin/users/{$user->id}/status", [
            'password' => 'AdminPassword123!'
        ], [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $tokens['access_token'],
            'HTTP_X_SESSION_ID' => $tokens['session_id']
        ]);

        $response2->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_active' => true
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'USER_ACTIVATED',
            'actor_id' => $admin->id
        ]);
    }

    public function test_admin_cannot_toggle_user_status_with_invalid_password()
    {
        $tokens = $this->getAdminTokens();
        $admin = $tokens['user'];

        // Seed admin password
        DB::table('user_credentials')->updateOrInsert(
            ['user_id' => $admin->id],
            [
                'password_hash' => \Illuminate\Support\Facades\Hash::make('AdminPassword123!'),
                'must_change_password' => false,
                'created_at' => now(),
                'updated_at' => now()
            ]
        );

        $user = User::create(['email' => 'employee@sbsi.com', 'is_active' => true]);

        $response = $this->call('PATCH', "/api/admin/users/{$user->id}/status", [
            'password' => 'WrongPassword!'
        ], [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $tokens['access_token'],
            'HTTP_X_SESSION_ID' => $tokens['session_id']
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }

    public function test_admin_cannot_deactivate_self()
    {
        $tokens = $this->getAdminTokens();
        $admin = $tokens['user'];

        DB::table('user_credentials')->updateOrInsert(
            ['user_id' => $admin->id],
            [
                'password_hash' => \Illuminate\Support\Facades\Hash::make('AdminPassword123!'),
                'must_change_password' => false,
                'created_at' => now(),
                'updated_at' => now()
            ]
        );

        $response = $this->call('PATCH', "/api/admin/users/{$admin->id}/status", [
            'password' => 'AdminPassword123!'
        ], [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $tokens['access_token'],
            'HTTP_X_SESSION_ID' => $tokens['session_id']
        ]);

        $response->assertStatus(422);
        $this->assertEquals('You cannot deactivate your own account.', $response->json('message'));
    }
}
