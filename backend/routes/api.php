<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\DeploymentController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AdminController;
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

// Admin
route::middleware(['auth:sanctum', 'role:super_admin|admin'])->group(function () {
    route::get('/audit-logs', [AuditLogController::class, 'index']);
    route::get('/users', [AdminController::class, 'users']);
    route::post('/users', [AdminController::class, 'store']);
    route::patch('/users/{user}', [AdminController::class, 'update']);
    route::delete('/users/{user}', [AdminController::class, 'destroy']);
    route::get('/projects', [AdminController::class, 'projects']);
    route::get('/servers', [AdminController::class, 'servers']);
});
