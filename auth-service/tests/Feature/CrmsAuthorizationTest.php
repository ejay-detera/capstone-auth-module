<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmsAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seed(\Database\Seeders\DepartmentSeeder::class);
        $this->seed(\Database\Seeders\UserSeeder::class);
    }

    /**
     * Test that CRMS permissions are correctly seeded and tagged.
     */
    public function test_crms_permissions_are_seeded()
    {
        $this->assertDatabaseHas('permissions', [
            'slug' => 'crms.roles.manage',
            'system' => 'crms'
        ]);

        $this->assertDatabaseHas('permissions', [
            'slug' => 'crms.ocr.upload',
            'system' => 'crms'
        ]);
    }

    /**
     * Test that Admin role has CRMS admin permissions.
     */
    public function test_admin_has_correct_crms_permissions()
    {
        $adminRole = Role::where('name', 'Admin')->first();
        
        $this->assertTrue($adminRole->permissions->contains('slug', 'crms.roles.manage'));
        $this->assertTrue($adminRole->permissions->contains('slug', 'crms.templates.manage'));
        
        // Admin should have OCR Upload permission in this setup
        $this->assertTrue($adminRole->permissions->contains('slug', 'crms.ocr.upload'));
    }

    /**
     * Test that Sales role has the full OCR/Risk suite.
     */
    public function test_sales_has_ocr_and_risk_permissions()
    {
        $salesRole = Role::where('name', 'Sales')->first();
        
        $this->assertTrue($salesRole->permissions->contains('slug', 'crms.ocr.upload'));
        $this->assertTrue($salesRole->permissions->contains('slug', 'crms.risk.assess'));
        $this->assertTrue($salesRole->permissions->contains('slug', 'crms.risk.approve'));
    }

    /**
     * Test the API endpoint filtering by system.
     */
    public function test_api_returns_filtered_permissions()
    {
        // Find or create a sales user
        $user = User::where('email', 'sales@example.com')->first();
        
        // Create a valid session to satisfy CheckActiveSession middleware
        $sessionId = (string) \Illuminate\Support\Str::uuid();
        \Illuminate\Support\Facades\DB::table('user_sessions')->insert([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Testing',
            'last_active_at' => now(),
            'is_active' => true,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user)
                         ->withHeader('X-Session-ID', $sessionId)
                         ->getJson('/api/me/permissions?system=crms');

        $response->assertStatus(200)
                 ->assertJsonFragment(['permissions' => [
                     'crms.templates.use',
                     'crms.ocr.upload',
                     'crms.ocr.process',
                     'crms.ocr.review',
                     'crms.contracts.generate',
                     'crms.risk.assess',
                     'crms.risk.view',
                     'crms.risk.approve',
                     'crms.contracts.view',
                     'crms.users.view',
                     'crms.partners.view'
                 ]]);
                 
        // Ensure auth-service internal permissions are NOT included in CRMS filtered request
        $response->assertJsonMissing(['view-dashboard']);
    }
}
