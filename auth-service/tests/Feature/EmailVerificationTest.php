<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\EmailVerificationToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyEmail;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function withSession($user)
    {
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

        return $this->actingAs($user)->withHeader('X-Session-ID', $sessionId);
    }

    public function test_user_can_request_verification_email()
    {
        Mail::fake();

        $user = User::factory()->create(['email_verified' => false]);
        
        $response = $this->withSession($user)->postJson('/api/send-verification');

        $response->assertStatus(200);
        $this->assertDatabaseHas('email_verification_tokens', [
            'user_id' => $user->id,
        ]);

        Mail::assertQueued(VerifyEmail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_user_can_verify_email_with_valid_token()
    {
        $user = User::factory()->create(['email_verified' => false]);
        $tokenPlain = 'valid-token';
        $tokenHash = hash('sha256', $tokenPlain);

        EmailVerificationToken::create([
            'user_id' => $user->id,
            'token_hash' => $tokenHash,
            'expires_at' => now()->addHours(24),
        ]);

        $response = $this->getJson("/api/verify-email?token={$tokenPlain}");

        $response->assertStatus(200);
        $this->assertTrue($user->fresh()->email_verified);
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertNotNull(EmailVerificationToken::where('token_hash', $tokenHash)->first()->used_at);
    }

    public function test_cannot_verify_email_with_expired_token()
    {
        $user = User::factory()->create(['email_verified' => false]);
        $tokenPlain = 'expired-token';
        $tokenHash = hash('sha256', $tokenPlain);

        EmailVerificationToken::create([
            'user_id' => $user->id,
            'token_hash' => $tokenHash,
            'expires_at' => now()->subHour(),
        ]);

        $response = $this->getJson("/api/verify-email?token={$tokenPlain}");

        $response->assertStatus(400);
        $this->assertFalse($user->fresh()->email_verified);
    }

    public function test_cannot_verify_email_with_already_used_token()
    {
        $user = User::factory()->create(['email_verified' => false]);
        $tokenPlain = 'used-token';
        $tokenHash = hash('sha256', $tokenPlain);

        EmailVerificationToken::create([
            'user_id' => $user->id,
            'token_hash' => $tokenHash,
            'expires_at' => now()->addHours(24),
            'used_at' => now()->subHour(),
        ]);

        $response = $this->getJson("/api/verify-email?token={$tokenPlain}");

        $response->assertStatus(400);
        $this->assertFalse($user->fresh()->email_verified);
    }

    public function test_resend_is_throttled()
    {
        $user = User::factory()->create(['email_verified' => false]);
        $rateLimitKey = "email_verify:{$user->id}";

        // Simulate 3 hits in the last 24 hours
        for ($i = 0; $i < 3; $i++) {
            DB::table('rate_limit_log')->insert([
                'key' => $rateLimitKey,
                'hits' => 1,
                'window_start' => now()
            ]);
        }

        $response = $this->withSession($user)->postJson('/api/send-verification');

        $response->assertStatus(429);
    }

    public function test_verified_middleware_blocks_unverified_users()
    {
        $user = User::factory()->create(['email_verified' => false]);

        // Define a temporary route to test middleware
        \Illuminate\Support\Facades\Route::get('/test-verified', function () {
            return response()->json(['message' => 'Verified!']);
        })->middleware(['auth:sanctum', 'active.session', 'verified']);

        $response = $this->withSession($user)->getJson('/test-verified');

        $response->assertStatus(403);
    }

    public function test_verified_middleware_allows_verified_users()
    {
        $user = User::factory()->create(['email_verified' => true]);

        \Illuminate\Support\Facades\Route::get('/test-verified-allow', function () {
            return response()->json(['message' => 'Verified!']);
        })->middleware(['auth:sanctum', 'active.session', 'verified']);

        $response = $this->withSession($user)->getJson('/test-verified-allow');

        $response->assertStatus(200);
    }
}
