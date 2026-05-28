<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserCredential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        UserCredential::create([
            'user_id' => $this->user->id,
            'password_hash' => Hash::make('Password123!', ['rounds' => 12]),
        ]);
    }

    /**
     * Test successful login.
     */
    public function test_login_success()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'access_token',
            'token_type',
            'session_id',
            'user'
        ]);
        $this->assertNotNull($response->json('access_token'));
    }

    /**
     * Test login with wrong password.
     */
    public function test_login_wrong_password()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'WrongPassword',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    /**
     * Test account locking after multiple failed attempts.
     */
    public function test_login_account_locking()
    {
        $email = 'test@example.com';

        // Clear rate limiter for test consistency
        RateLimiter::clear('login:' . $email . '|127.0.0.1');

        // Fail 3 times
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/login', [
                'email' => $email,
                'password' => 'WrongPassword',
            ]);
        }

        // 4th attempt should be locked
        $response = $this->postJson('/api/login', [
            'email' => $email,
            'password' => 'Password123!', // Correct password but should be locked
        ]);

        $response->assertStatus(429);
        $response->assertJsonFragment([
            'message' => 'Too many login attempts. Please try again in 15 minutes.'
        ]);
    }

    /**
     * Test logout.
     */
    public function test_logout_revokes_token()
    {
        // Login first
        $loginResponse = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ]);

        $token = $loginResponse->json('access_token');
        $sessionId = $loginResponse->json('session_id');

        // Logout with token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-Session-ID' => $sessionId,
        ])->postJson('/api/logout');

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Successfully logged out.']);

        // Verify token no longer works
        $userResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/user');

        $userResponse->assertStatus(401);
    }
}
