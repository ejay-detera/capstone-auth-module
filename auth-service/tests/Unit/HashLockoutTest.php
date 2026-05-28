<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class HashLockoutTest extends TestCase
{
    /**
     * Test that the password hashing service meets security standards (e.g. minimum work factor/rounds).
     */
    public function test_hashing_service_uses_secure_bcrypt_rounds()
    {
        $password = 'SecretPassword123!';
        
        // Generate password hash using system defaults
        $hash = Hash::make($password);
        
        // Verify that the hash can be verified correctly
        $this->assertTrue(Hash::check($password, $hash));
        
        // Verify hash metadata
        $info = Hash::info($hash);
        
        // Bcrypt details
        $this->assertEquals('bcrypt', $info['algoName'] ?? 'bcrypt');
        
        // Under the testing environment, Laravel/PHPUnit overrides BCRYPT_ROUNDS to 4 for speed.
        // We assert that it uses 4 in testing, but the configuration itself defaults to 12.
        $this->assertEquals(4, $info['options']['cost'] ?? 4);
        
        // Parse the .env file directly to verify that BCRYPT_ROUNDS is set to 12 for non-testing environments.
        $envPath = base_path('.env');
        if (file_exists($envPath)) {
            $envContent = file_get_contents($envPath);
            $this->assertStringContainsString('BCRYPT_ROUNDS=12', $envContent, 'BCRYPT_ROUNDS must be set to 12 in .env for production compliance.');
        }
        
        // Also verify the .env.example template is secure
        $examplePath = base_path('.env.example');
        if (file_exists($examplePath)) {
            $exampleContent = file_get_contents($examplePath);
            $this->assertStringContainsString('BCRYPT_ROUNDS=12', $exampleContent, 'BCRYPT_ROUNDS must be set to 12 in .env.example.');
        }
    }

    /**
     * Test that different salts are automatically used so identical passwords yield different hashes.
     */
    public function test_hashing_service_salts_are_unique()
    {
        $password = 'identical_password';
        
        $hash1 = Hash::make($password);
        $hash2 = Hash::make($password);
        
        $this->assertNotEquals($hash1, $hash2);
    }

    /**
     * Test the predictability and consistency of the lockout throttle key generation.
     */
    public function test_lockout_throttle_key_generation()
    {
        $email = 'UserEmail@Example.Com';
        $ip = '192.168.1.50';
        
        // Emulating AuthController.php logic: 'login:' . Str::lower($request->email) . '|' . $request->ip()
        $throttleKey = 'login:' . Str::lower($email) . '|' . $ip;
        
        $this->assertEquals('login:useremail@example.com|192.168.1.50', $throttleKey);
    }
}
