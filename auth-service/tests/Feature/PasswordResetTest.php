<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordResetMail;
use Tests\TestCase;
use Illuminate\Support\Str;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        
        // Ensure admin user exists with credentials
        $user = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['is_active' => true]
        );
        
        DB::table('user_credentials')->updateOrInsert(
            ['user_id' => $user->id],
            ['password_hash' => Hash::make('password'), 'created_at' => now(), 'updated_at' => now()]
        );
    }

    public function test_forgot_password_generates_token_and_sends_email()
    {
        Mail::fake();

        $response = $this->postJson('/api/forgot-password', [
            'email' => 'admin@example.com'
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'If an account with that email exists, a password reset link has been sent.']);

        $user = User::where('email', 'admin@example.com')->first();
        
        $this->assertDatabaseCount('password_reset_tokens', 1);

        Mail::assertQueued(PasswordResetMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_forgot_password_rate_limiting()
    {
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/forgot-password', [
                'email' => 'admin@example.com'
            ]);
        }

        $response = $this->postJson('/api/forgot-password', [
            'email' => 'admin@example.com'
        ]);

        $response->assertStatus(429)
                 ->assertHeader('Retry-After', 3600);
    }

    public function test_reset_password_success()
    {
        $user = User::where('email', 'admin@example.com')->first();
        $tokenPlain = Str::random(64);
        
        DB::table('password_reset_tokens')->insert([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $tokenPlain),
            'expires_at' => now()->addMinutes(15),
            'created_at' => now()
        ]);

        // Create a dummy session and refresh token
        DB::table('user_sessions')->insert([
            'user_id' => $user->id,
            'session_id' => Str::uuid(),
            'is_active' => true,
            'created_at' => now()
        ]);
        
        DB::table('refresh_tokens')->insert([
            'user_id' => $user->id,
            'token_hash' => 'dummy',
            'is_revoked' => false,
            'expires_at' => now()->addDays(30),
            'created_at' => now()
        ]);

        $response = $this->postJson('/api/reset-password', [
            'token' => $tokenPlain,
            'password' => 'NewPass123!@#'
        ]);

        $response->assertStatus(200);

        // Check token marked as used
        $this->assertDatabaseHas('password_reset_tokens', [
            'token_hash' => hash('sha256', $tokenPlain),
        ]);
        $tokenRecord = DB::table('password_reset_tokens')->where('token_hash', hash('sha256', $tokenPlain))->first();
        $this->assertNotNull($tokenRecord->used_at);

        // Check password policy fields
        $credentials = DB::table('user_credentials')->where('user_id', $user->id)->first();
        $this->assertEquals(0, $credentials->must_change_password);
        $this->assertNotNull($credentials->password_changed_at);
        $this->assertTrue(Hash::check('NewPass123!@#', $credentials->password_hash));

        // Check active sessions revoked
        $this->assertDatabaseMissing('user_sessions', [
            'user_id' => $user->id,
            'is_active' => true
        ]);

        $this->assertDatabaseMissing('refresh_tokens', [
            'user_id' => $user->id,
            'is_revoked' => false
        ]);
    }

    public function test_reset_password_fails_with_expired_token()
    {
        $user = User::where('email', 'admin@example.com')->first();
        $tokenPlain = Str::random(64);
        
        DB::table('password_reset_tokens')->insert([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $tokenPlain),
            'expires_at' => now()->subMinutes(1),
            'created_at' => now()
        ]);

        $response = $this->postJson('/api/reset-password', [
            'token' => $tokenPlain,
            'password' => 'NewPass123!@#'
        ]);

        $response->assertStatus(400);
    }

    public function test_reset_password_fails_with_used_token()
    {
        $user = User::where('email', 'admin@example.com')->first();
        $tokenPlain = Str::random(64);
        
        DB::table('password_reset_tokens')->insert([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $tokenPlain),
            'expires_at' => now()->addMinutes(15),
            'used_at' => now(),
            'created_at' => now()
        ]);

        $response = $this->postJson('/api/reset-password', [
            'token' => $tokenPlain,
            'password' => 'NewPass123!@#'
        ]);

        $response->assertStatus(400);
    }

    public function test_reset_password_fails_policy()
    {
        $response = $this->postJson('/api/reset-password', [
            'token' => 'dummy',
            'password' => 'weak'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }
}
