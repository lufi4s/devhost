<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AdminController extends Controller
{
    /**
     * GET /api/admin/users
     * Admin-only. Lists all users with their roles.
     */
    public function users(): JsonResponse
    {
        return response()->json([
            'data' => User::with('role')->latest()->get(),
        ]);
    }

    /**
     * POST /api/admin/users
     * Admin-only. Create a user.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'role' => ['sometimes', 'in:super_admin,admin,developer'],
        ]);

        $role = $request->input('role', 'developer');

        $user = User::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
        ]);

        $user->syncRoles($role);

        AuditService::log('user.created', "Created user {$user->email}", [
            'user_id' => $user->id,
            'role' => $role,
        ], Auth::user());

        return response()->json(['user' => $user->load('role')], 201);
    }

    /**
     * PATCH /api/admin/users/{user}
     * Admin-only. Update a user's role.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'role' => ['sometimes', 'in:super_admin,admin,developer'],
        ]);

        $role = $request->input('role');
        if ($role) {
            $user->syncRoles($role);
        }

        AuditService::log('user.updated', "Updated user {$user->email}", [
            'user_id' => $user->id,
            'role' => $role,
        ], Auth::user());

        return response()->json(['user' => $user->load('role')]);
    }

    /**
     * DELETE /api/admin/users/{user}
     * Admin-only. Delete a user.
     */
    public function destroy(User $user): JsonResponse
    {
        if ($user->id === Auth::id()) {
            return response()->json([
                'message' => 'You cannot delete your own account.',
            ], 422);
        }

        AuditService::log('user.deleted', "Deleted user {$user->email}", [
            'user_id' => $user->id,
        ], Auth::user());

        $user->delete();

        return response()->json(['message' => 'User deleted.']);
    }

    /**
     * GET /api/admin/projects
     * Admin-only. Lists all projects.
     */
    public function projects(Request $request): JsonResponse
    {
        return response()->json(Project::with(['domains', 'databases', 'latestDeployment', 'user:name,email'])
            ->latest()
            ->paginate($request->integer('per_page', 15)));
    }

    /**
     * GET /api/admin/servers
     * Admin-only. Lists all servers.
     */
    public function servers(): JsonResponse
    {
        return response()->json([
            'servers' => Server::latest()->get(),
        ]);
    }
}
