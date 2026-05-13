<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class LoginTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_successful_login(): void
    {
        $response = $this->postJson('/api/login', [
            'username' => 'admin',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['access_token', 'token_type', 'user'])
                 ->assertCookie('refresh_token');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'LOGIN_SUCCESS',
            'description' => 'Successful login for username: admin'
        ]);

        $this->assertDatabaseHas('user_sessions', [
            'is_active' => true
        ]);
    }

    public function test_invalid_credentials(): void
    {
        $response = $this->postJson('/api/login', [
            'username' => 'admin',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['username']);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'LOGIN_FAILED',
            'description' => 'Failed login attempt for username: admin'
        ]);
    }

    public function test_account_lockout_after_three_failures(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/login', [
                'username' => 'lockeduser',
                'password' => 'wrongpassword',
            ]);
        }

        $response = $this->postJson('/api/login', [
            'username' => 'lockeduser',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(429)
                 ->assertJsonFragment(['message' => 'Too many login attempts. Please try again in 15 minutes.']);
    }

    public function test_audit_logs_record_actor_id_on_failure_if_user_exists(): void
    {
        $this->postJson('/api/login', [
            'username' => 'admin',
            'password' => 'wrongpassword',
        ]);

        $user = \App\Models\User::where('username', 'admin')->first();

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $user->id,
            'action' => 'LOGIN_FAILED'
        ]);
    }
}
