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
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        return $this->buildUserQuery($request)->paginate($request->input('per_page', 15));
    }

    private function buildUserQuery(Request $request)
    {
        $query = User::with(['profile.role', 'profile.department']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhereHas('profile', function ($pq) use ($search) {
                      $pq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('role_id')) {
            $query->filterByRole($request->role_id);
        }

        if ($request->filled('department_id')) {
            $query->filterByDepartment($request->department_id);
        }

        if ($request->filled('is_active')) {
            $query->filterByStatus($request->is_active);
        }

        $user = $request->user();
        if ($user && $user->profile->role->name !== 'IT Admin' && $user->profile->role->name !== 'Super Admin') {
            if ($user->profile->department->name === 'Finance' && $user->profile->role->name === 'Admin') {
                $query->whereHas('profile', function($q) use ($user) {
                    $q->where('department_id', $user->profile->department_id);
                });
            } else {
                // If not IT Admin and not Finance Admin, return nothing or fail
                $query->where('id', -1);
            }
        }

        return $query;
    }

    public function show($id)
    {
        return User::with(['profile.role', 'profile.department'])->findOrFail($id);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name'      => 'required|string|max:255',
            'last_name'       => 'required|string|max:255',
            'email'           => ['required', 'email', 'unique:users,email', 'regex:/@sbsi\.com$/i'],
            'role_id'         => 'required_without:role_name|nullable|exists:roles,id',
            'role_name'       => 'required_without:role_id|nullable|string|exists:roles,name',
            'department_id'   => 'required_without:department_name|nullable|exists:departments,id',
            'department_name' => 'required_without:department_id|nullable|string|exists:departments,name',
        ], [
            'email.unique' => 'An account with this email already exists.',
            'email.regex'  => 'Email must use the company domain @sbsi.com.',
        ]);

        // Resolve role_name -> role_id
        if (empty($validated['role_id']) && !empty($validated['role_name'])) {
            $role = \App\Models\Role::where('name', $validated['role_name'])->first();
            $validated['role_id'] = $role?->id;
        }

        // Resolve department_name -> department_id
        if (empty($validated['department_id']) && !empty($validated['department_name'])) {
            $dept = \App\Models\Department::where('name', $validated['department_name'])->first();
            $validated['department_id'] = $dept?->id;
        }

        if (empty($validated['role_id']) || empty($validated['department_id'])) {
            return response()->json(['message' => 'Invalid role or department provided.'], 422);
        }

        $actor = $request->user();
        $actorProfile = $actor->profile;
        $actorRole = $actorProfile->role->name ?? '';
        $actorDept = $actorProfile->department->name ?? '';

        $isITAdmin = in_array($actorRole, ['IT Admin', 'Super Admin']);

        if (!$isITAdmin) {
            if ($actorDept === 'Finance' && $actorRole === 'Admin') {
                $financeDept = \App\Models\Department::where('name', 'Finance')->first();
                if ($financeDept && $validated['department_id'] != $financeDept->id) {
                    return response()->json(['message' => 'You can only create accounts for the Finance department.'], 403);
                }

                $allowedRoleNames = ['Manager', 'Employee'];
                $assignedRole = \App\Models\Role::find($validated['role_id']);
                if (!$assignedRole || !in_array($assignedRole->name, $allowedRoleNames)) {
                    return response()->json(['message' => 'You are only authorized to assign Manager or Employee roles.'], 403);
                }
            } else {
                return response()->json(['message' => 'Unauthorized to create accounts.'], 403);
            }
        }

        $tempPassword = $this->generateSecurePassword();

        try {
            DB::beginTransaction();

            $user = User::create([
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
                'description' => 'Admin created account for ' . $user->email,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'action_date' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            // Targeted cache invalidation instead of Cache::flush()
            try {
                Cache::forget('roles:list');
                Cache::forget('roles:all');
                Cache::forget('departments:all');
            } catch (\Exception $e) {
                Log::warning('Failed to invalidate cache after user creation', ['error' => $e->getMessage()]);
            }

            Mail::to($user->email)->queue(new WelcomeEmail($user->email, $tempPassword));

            return response()->json([
                'message' => 'User created successfully.',
                'user' => $user->load(['profile.role', 'profile.department'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create user.', 'error' => $e->getMessage()], 500);
        }
    }

    public function getRoles(Request $request)
    {
        try {
            $roles = Cache::remember('roles:all', 3600, function() {
                return \App\Models\Role::all()->toArray();
            });

            $actor = $request->user();
            if ($actor && !in_array($actor->profile->role->name, ['IT Admin', 'Super Admin'])) {
                if ($actor->profile->department->name === 'Finance' && $actor->profile->role->name === 'Admin') {
                    $allowed = ['Manager', 'Employee'];
                    $roles = array_filter($roles, function($r) use ($allowed) {
                        return in_array($r['name'], $allowed);
                    });
                    $roles = array_values($roles);
                } else {
                    $roles = [];
                }
            }

            return response()->json($roles);
        } catch (\Exception $e) {
            return response()->json(\App\Models\Role::all());
        }
    }

    public function getDepartments(Request $request)
    {
        try {
            $departments = Cache::remember('departments:all', 3600, function() {
                return \App\Models\Department::all()->toArray();
            });

            $actor = $request->user();
            if ($actor && !in_array($actor->profile->role->name, ['IT Admin', 'Super Admin'])) {
                if ($actor->profile->department->name === 'Finance' && $actor->profile->role->name === 'Admin') {
                    $departments = array_filter($departments, function($d) {
                        return $d['name'] === 'Finance';
                    });
                    $departments = array_values($departments);
                } else {
                    $departments = [];
                }
            }

            return response()->json($departments);
        } catch (\Exception $e) {
            return response()->json(\App\Models\Department::all());
        }
    }

    public function getUserPermissions($id)
    {
        // For security, only allow the user to see their own permissions or admins to see anyone's
        if (Auth::id() != $id && Gate::denies('manage-users')) {
            abort(403, 'Unauthorized.');
        }

        try {
            return Cache::store('database')->remember("permissions:user:{$id}", 300, function () use ($id) {
                return $this->fetchUserPermissions($id);
            });
        } catch (\Exception $e) {
            Log::warning('Cache unavailable for user permissions, querying DB directly', ['error' => $e->getMessage()]);
            return $this->fetchUserPermissions($id);
        }
    }

    private function fetchUserPermissions($id)
    {
        $user = User::with('profile.role.permissions')->findOrFail($id);
        
        if (!$user->profile || !$user->profile->role) {
            return [];
        }

        return $user->profile->role->permissions->pluck('slug')->toArray();
    }

    private function generateSecurePassword()
    {
        return Str::random(8) . 'A1!'; // Simple way to meet policy (min 8, 1 uppercase, 1 number, 1 special char)
    }
}
