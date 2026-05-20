<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
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
    public function test_required_tables_exist(): void
    {
        $tables = [
            'users', 'user_credentials', 'user_profiles', 'roles', 'permissions', 
            'role_permission', 'departments', 'audit_logs', 'mfa_configs',
            'refresh_tokens', 'password_reset_tokens', 'email_verification_tokens',
            'user_sessions', 'login_history', 'rate_limit_log'
        ];

        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Table $table is missing.");
        }
    }

    public function test_required_indexes_exist(): void
    {
        $this->assertTrue(Schema::hasIndex('users', ['email']));
        $this->assertTrue(Schema::hasIndex('users', ['is_active']));
        $this->assertTrue(Schema::hasIndex('user_credentials', ['user_id']));
        $this->assertTrue(Schema::hasIndex('audit_logs', ['actor_id']));
        $this->assertTrue(Schema::hasIndex('audit_logs', ['action_date']));
        $this->assertTrue(Schema::hasIndex('user_sessions', ['session_id']));
        $this->assertTrue(Schema::hasIndex('user_sessions', ['user_id', 'is_active']));
        $this->assertTrue(Schema::hasIndex('login_history', ['user_id']));
        $this->assertTrue(Schema::hasIndex('refresh_tokens', ['user_id', 'is_revoked']));
        $this->assertTrue(Schema::hasIndex('role_permission', ['role_id', 'permission_id']));
    }
    
    public function test_seed_data_is_correct(): void
    {
        $this->assertDatabaseHas('roles', ['name' => 'Super Admin']);
        $this->assertDatabaseHas('roles', ['name' => 'IT Admin']);
        $this->assertDatabaseHas('roles', ['name' => 'Manager']);
        $this->assertDatabaseHas('roles', ['name' => 'Employee']);
    }
}
