<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use App\Services\PlanService;
use App\Services\SubscriptionService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function __construct(
        private PlanService $planService,
        private SubscriptionService $subscriptionService,
    ) {
    }

    /**
     * POST /api/auth/register
     * Open in local dev; lock down in production via a permission policy.
     *
     * Accepts an optional `plan_slug` so a new customer can subscribe on
     * signup. When omitted, the account is created without a subscription
     * (legacy behaviour preserved for local dev / admin-created users).
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'plan_slug' => ['sometimes', 'string', 'exists:plans,slug'],
            'billing_cycle' => ['sometimes', 'in:monthly,quarterly,annually'],
        ]);

        $user = UserService::createDeveloper(
            $request->input('name'),
            $request->input('email'),
            $request->input('password')
        );

        $payload = [
            'plan_slug' => $request->input('plan_slug'),
            'billing_cycle' => $request->input('billing_cycle', 'monthly'),
        ];

        $subscription = null;
        if ($request->filled('plan_slug')) {
            $resolved = $this->planService->resolvePlan($payload);
            if ($resolved['ok']) {
                $subscription = $this->subscriptionService->subscribe(
                    $user,
                    $resolved['plan'],
                    $resolved['plan']->billing_cycle
                );
            }
        }

        AuditService::log('user.registered', "Registered user {$user->email}", [
            'user_id' => $user->id,
            'plan_slug' => $request->input('plan_slug'),
        ], $user);

        return response()->json([
            'token' => $user->createToken('api')->plainTextToken,
            'user' => $user->load('currentPlan'),
            'subscription' => $subscription,
        ], 201);
    }

    /**
     * POST /api/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        $user = Auth::user();

        return response()->json([
            'token' => $user->createToken('api')->plainTextToken,
            'user' => $user,
        ]);
    }

    /**
     * POST /api/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    /**
     * GET /api/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $request->user()]);
    }
}
