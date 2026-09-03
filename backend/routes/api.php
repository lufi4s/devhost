<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\DeploymentController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\DomainController;
use App\Http\Controllers\Api\DnsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| All routes require a Sanctum bearer token except login/register.
|
*/

// Auth
route::post('/auth/login', [AuthController::class, 'login']);
route::post('/auth/register', [AuthController::class, 'register']);
route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
route::get('/auth/me', [AuthController::class, 'me'])->middleware('auth:sanctum');

// Projects (auth required)
route::middleware('auth:sanctum')->group(function () {
    route::get('/projects', [ProjectController::class, 'index']);
    route::post('/projects', [ProjectController::class, 'store']);
    route::get('/projects/{project}', [ProjectController::class, 'show']);
    route::patch('/projects/{project}', [ProjectController::class, 'update']);
    route::delete('/projects/{project}', [ProjectController::class, 'destroy']);

    route::post('/projects/{project}/deploy', [DeploymentController::class, 'deploy']);
    route::post('/projects/{project}/restart', [DeploymentController::class, 'restart']);
    route::post('/projects/{project}/stop', [DeploymentController::class, 'stop']);
    route::post('/projects/{project}/start', [DeploymentController::class, 'start']);
    route::get('/projects/{project}/deployments', [DeploymentController::class, 'index']);
    route::get('/projects/{project}/logs', [DeploymentController::class, 'logs']);
    route::get('/projects/{project}/metrics', [DeploymentController::class, 'metrics']);

    route::get('/projects/{project}/env', [ProjectController::class, 'env']);
    route::post('/projects/{project}/env', [ProjectController::class, 'storeEnv']);
    route::delete('/projects/{project}/env/{env}', [ProjectController::class, 'deleteEnv']);

    route::get('/projects/{project}/databases', [ProjectController::class, 'databases']);
    route::post('/projects/{project}/databases', [ProjectController::class, 'createDatabase']);
});

// Plans (readable by anyone authenticated; write only by admins)
route::middleware('auth:sanctum')->group(function () {
    route::get('/plans', [PlanController::class, 'index']);
    route::get('/plans/{plan}', [PlanController::class, 'show']);

    route::middleware('role:super_admin|admin')->group(function () {
        route::post('/plans', [PlanController::class, 'store']);
        route::patch('/plans/{plan}', [PlanController::class, 'update']);
        route::delete('/plans/{plan}', [PlanController::class, 'destroy']);
    });
});

// Billing / subscriptions (customer-scoped)
route::middleware('auth:sanctum')->group(function () {
    route::get('/billing/plans', [BillingController::class, 'plans']);
    route::post('/billing/subscribe', [BillingController::class, 'subscribe']);
    route::get('/billing/subscription', [BillingController::class, 'show']);
    route::patch('/billing/subscription/plan', [BillingController::class, 'changePlan']);
    route::post('/billing/subscription/renew', [BillingController::class, 'renew']);
    route::get('/billing/invoices', [BillingController::class, 'invoices']);
});

// Customer domains + DNS (tenant-scoped: every lookup is filtered by customer_id)
route::middleware('auth:sanctum')->group(function () {
    route::get('/domains', [DomainController::class, 'index']);
    route::post('/domains', [DomainController::class, 'store']);
    route::delete('/domains/{domain}', [DomainController::class, 'destroy']);
    route::post('/domains/{domain}/set-primary', [DomainController::class, 'setPrimary']);
    route::post('/domains/{domain}/configure-managed', [DomainController::class, 'configureManaged']);

    route::get('/domains/{domain}/dns-records', [DnsController::class, 'index']);
    route::post('/domains/{domain}/dns-records', [DnsController::class, 'store']);
    route::delete('/domains/{domain}/dns-records/{record}', [DnsController::class, 'destroy']);
});

// Admin
route::middleware(['auth:sanctum', 'role:super_admin|admin'])->group(function () {
    route::get('/admin/audit-logs', [AuditLogController::class, 'index']);
    route::get('/admin/users', [AdminController::class, 'users']);
    route::post('/admin/users', [AdminController::class, 'store']);
    route::patch('/admin/users/{user}', [AdminController::class, 'update']);
    route::delete('/admin/users/{user}', [AdminController::class, 'destroy']);
    route::get('/admin/projects', [AdminController::class, 'projects']);
    route::get('/admin/servers', [AdminController::class, 'servers']);
});
