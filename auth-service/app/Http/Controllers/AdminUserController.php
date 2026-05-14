<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserProfile;
use App\Mail\WelcomeEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $key = 'users:list:' . md5(serialize($request->all()));
        
        return Cache::remember($key, 300, function() use ($request) {
            $query = User::with(['profile.role', 'profile.department']);
// ... existing logic ...
            return $query->paginate($request->get('per_page', 15));
        });
    }

    public function show($id)
    {
        return User::with(['profile.role', 'profile.department'])->findOrFail($id);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'username' => 'required|string|unique:users,username',
            'role_id' => 'required|exists:roles,id',
            'department_id' => 'required|exists:departments,id',
        ]);

        $tempPassword = $this->generateSecurePassword();

        try {
            DB::beginTransaction();

            $user = User::create([
                'username' => $validated['username'],
                'email' => $validated['email'],
                'is_active' => true,
            ]);

            UserProfile::create([
                'user_id' => $user->id,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'role_id' => $validated['role_id'],
                'department_id' => $validated['department_id'],
            ]);

            DB::table('user_credentials')->insert([
                'user_id' => $user->id,
                'password_hash' => Hash::make($tempPassword),
                'must_change_password' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('audit_logs')->insert([
                'actor_id' => $request->user()->id ?? null,
                'action' => 'ACCOUNT_CREATED',
                'description' => 'Admin created account for ' . $user->username,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'action_date' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            Cache::forget('users:list:*'); // Actually we need to forget the specific key or just clear all users
            // Since keys are dynamic, we might need a better way, but for now let's just clear
            Cache::flush(); 

            Mail::to($user->email)->queue(new WelcomeEmail($user->username, $tempPassword));

            return response()->json([
                'message' => 'User created successfully.',
                'user' => $user->load(['profile.role', 'profile.department'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create user.', 'error' => $e->getMessage()], 500);
        }
    }

    public function getRoles()
    {
        return Cache::remember('roles:all', 3600, function() {
            return \App\Models\Role::all();
        });
    }

    public function getDepartments()
    {
        return Cache::remember('departments:all', 3600, function() {
            return \App\Models\Department::all();
        });
    }

    private function generateSecurePassword()
    {
        return Str::random(8) . 'A1!'; // Simple way to meet policy (min 8, 1 uppercase, 1 number, 1 special char)
    }
}
