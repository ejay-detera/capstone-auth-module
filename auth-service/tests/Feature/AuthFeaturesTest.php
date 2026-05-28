<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Illuminate\Support\Str;

class AuthFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->disableCookieEncryption();
        
        $user = \App\Models\User::create([
            'email' => 'admin@example.com',
            'is_active' => true,
        ]);
        
        // Assuming there is a credentials table relationship for the password hash
        \Illuminate\Support\Facades\DB::table('user_credentials')->insert([
            'user_id' => $user->id,
            'password_hash' => \Illuminate\Support\Facades\Hash::make('password'),
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
    // Assume database has 'admin' / 'password' from seeders like LoginTest

    protected function getLoginTokens()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);
        
        $response->assertStatus(200);
        
        $cookies = $response->headers->getCookies();
        $refreshToken = null;
        $sessionId = null;
        
        foreach ($cookies as $cookie) {
            if ($cookie->getName() === 'refresh_token') {
                $refreshToken = $cookie->getValue();
            }
            if ($cookie->getName() === 'session_id') {
                $sessionId = $cookie->getValue();
            }
        }
        
        return [
            'access_token' => $response->json('access_token'),
            'refresh_token' => $refreshToken,
            'session_id' => $sessionId,
        ];
    }

    public function test_token_refresh_success_and_rotation()
    {
        $tokens = $this->getLoginTokens();
        
        $this->assertNotNull($tokens['refresh_token']);
        
        $response = $this->call('POST', '/api/refresh', [], [
            'refresh_token' => $tokens['refresh_token']
        ], [], ['HTTP_ACCEPT' => 'application/json']);
        
        if ($response->status() !== 200) {
            dump($response->json());
        }
        
        $response->assertStatus(200)
                 ->assertJsonStructure(['access_token', 'token_type', 'user']);
                 
        $cookies = $response->headers->getCookies();
        $newRefreshToken = null;
        foreach ($cookies as $cookie) {
            if ($cookie->getName() === 'refresh_token') {
                $newRefreshToken = $cookie->getValue();
            }
        }
        
        $this->assertNotNull($newRefreshToken);
        $this->assertNotEquals($tokens['refresh_token'], $newRefreshToken);
        
        // Old token should be revoked
        $oldHash = hash('sha256', $tokens['refresh_token']);
        $this->assertDatabaseHas('refresh_tokens', [
            'token_hash' => $oldHash,
            'is_revoked' => 1
        ]);
    }

    public function test_refresh_token_reuse_detection()
    {
        $tokens = $this->getLoginTokens();
        
        // Refresh once to rotate it
        $this->call('POST', '/api/refresh', [], [
            'refresh_token' => $tokens['refresh_token']
        ], [], ['HTTP_ACCEPT' => 'application/json'])
             ->assertStatus(200);
        
        // Now try to use the OLD revoked token
        $response = $this->call('POST', '/api/refresh', [], [
            'refresh_token' => $tokens['refresh_token']
        ], [], ['HTTP_ACCEPT' => 'application/json']);
        
        $response->assertStatus(401)
                 ->assertJsonFragment(['message' => 'Token compromise detected. All sessions revoked.']);
                 
        // Check that ALL tokens for this user are now revoked
        $user = \App\Models\User::where('email', 'admin@example.com')->first();
        $unrevokedCount = DB::table('refresh_tokens')
            ->where('user_id', $user->id)
            ->where('is_revoked', 0)
            ->count();
            
        $this->assertEquals(0, $unrevokedCount);
    }

    public function test_logout_revokes_tokens_and_session()
    {
        $tokens = $this->getLoginTokens();
        
        $response = $this->call('POST', '/api/logout', [], [
            'refresh_token' => $tokens['refresh_token'],
            'session_id' => $tokens['session_id']
        ], [], [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $tokens['access_token'],
            'HTTP_X_SESSION_ID' => $tokens['session_id']
        ]);
        
        $response->assertStatus(200);
        
        $hash = hash('sha256', $tokens['refresh_token']);
        $this->assertDatabaseHas('refresh_tokens', [
            'token_hash' => $hash,
            'is_revoked' => 1
        ]);
        
        $this->assertDatabaseHas('user_sessions', [
            'session_id' => $tokens['session_id'],
            'is_active' => 0
        ]);
    }

    public function test_middleware_rejection_for_inactive_session()
    {
        $tokens = $this->getLoginTokens();
        
        // Deactivate the session manually
        DB::table('user_sessions')
            ->where('session_id', $tokens['session_id'])
            ->update(['is_active' => 0]);
            
        $response = $this->call('GET', '/api/user', [], [
            'session_id' => $tokens['session_id']
        ], [], [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $tokens['access_token'],
            'HTTP_X_SESSION_ID' => $tokens['session_id']
        ]);
        
        $response->assertStatus(401)
                 ->assertJsonFragment(['message' => 'Session is inactive or invalid.']);
    }

    public function test_session_expiration_due_to_inactivity()
    {
        $tokens = $this->getLoginTokens();
        
        // Simulating inactivity by backdating `last_active_at` in the database to 121 minutes ago
        DB::table('user_sessions')
            ->where('session_id', $tokens['session_id'])
            ->update(['last_active_at' => now()->subMinutes(121)]);
            
        $response = $this->call('GET', '/api/user', [], [
            'session_id' => $tokens['session_id']
        ], [], [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $tokens['access_token'],
            'HTTP_X_SESSION_ID' => $tokens['session_id']
        ]);
        
        $response->assertStatus(401)
                 ->assertJsonFragment(['message' => 'Session expired due to inactivity.']);
                 
        // Verify that the session has been marked as inactive in the database
        $this->assertDatabaseHas('user_sessions', [
            'session_id' => $tokens['session_id'],
            'is_active' => 0
        ]);
    }
}
