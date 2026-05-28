<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * A basic feature test example.
     */
    public function test_successful_login(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['access_token', 'token_type', 'user'])
                 ->assertCookie('refresh_token');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'LOGIN_SUCCESS',
            'description' => 'Successful login for email: admin@example.com'
        ]);

        $this->assertDatabaseHas('user_sessions', [
            'is_active' => true
        ]);
    }

    public function test_invalid_credentials(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'admin@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'LOGIN_FAILED',
            'description' => 'Failed login attempt for email: admin@example.com'
        ]);
    }

    public function test_account_lockout_after_three_failures(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/login', [
                'email' => 'lockeduser@example.com',
                'password' => 'wrongpassword',
            ]);
        }

        $response = $this->postJson('/api/login', [
            'email' => 'lockeduser@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(429)
                 ->assertJsonFragment(['message' => 'Too many login attempts. Please try again in 15 minutes.']);
    }

    public function test_audit_logs_record_actor_id_on_failure_if_user_exists(): void
    {
        $this->postJson('/api/login', [
            'email' => 'admin@example.com',
            'password' => 'wrongpassword',
        ]);

        $user = \App\Models\User::where('email', 'admin@example.com')->first();

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $user->id,
            'action' => 'LOGIN_FAILED'
        ]);
    }

    public function test_account_automatic_unlock_after_decay_period(): void
    {
        $email = 'lockeduser@example.com';
        
        // Clear rate limiter for test consistency
        \Illuminate\Support\Facades\RateLimiter::clear('login:' . $email . '|127.0.0.1');
        
        // Fail 3 times to lock the account
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/login', [
                'email' => $email,
                'password' => 'wrongpassword',
            ]);
        }

        // Verify it is indeed locked
        $response = $this->postJson('/api/login', [
            'email' => $email,
            'password' => 'wrongpassword',
        ]);
        $response->assertStatus(429);

        // Simulate 15 minutes (901 seconds) passing to decay the rate limit
        $this->travel(901)->seconds();

        // The account should be automatically unlocked and allow login attempts again
        $unlockedResponse = $this->postJson('/api/login', [
            'email' => $email,
            'password' => 'wrongpassword',
        ]);

        // It should return 422 (invalid credentials) instead of 429 (locked)
        $unlockedResponse->assertStatus(422);
    }
}
