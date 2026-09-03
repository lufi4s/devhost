<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\AuditService;
use App\Services\ProvisioningService;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    public function __construct(private ProjectService $projectService)
    {
    }

    /**
     * GET /api/projects
     */
    public function index(Request $request): JsonResponse
    {
        $query = Auth::user()->projects();

        if (! Auth::user()->isSuperAdmin()) {
            // Developers only see their own projects.
            $query->where('user_id', Auth::id());
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return response()->json($query
            ->with(['domains', 'databases', 'latestDeployment'])
            ->latest()
            ->paginate($request->integer('per_page', 15)));
    }

    /**
     * POST /api/projects
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->projectService->validateCreate($request->all());

        $hostname = "{$data['subdomain']}.{$data['domain']}";

        if ($this->projectService->hostnameExists($hostname)) {
            return response()->json([
                'message' => 'A project with this subdomain/domain already exists.',
            ], 422);
        }

        $project = Auth::user()->projects()->create([
            ...$data,
            'status' => 'provisioning',
        ]);

        // Dispatch the provisioning job — never provision synchronously.
        \App\Jobs\ProvisionProjectJob::dispatch($project);

        AuditService::log('project.created', "Created project \"{$project->name}\"", [
            'project_id' => $project->id,
            'type' => $project->type,
            'hostname' => $project->hostname,
        ]);

        return response()->json([
            'project' => $project->load(['domains', 'databases']),
            'message' => 'Project created. Provisioning started.',
        ], 201);
    }

    /**
     * GET /api/projects/{project}
     */
    public function show(Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        return response()->json([
            'project' => $project->load([
                'domains',
                'databases',
                'environmentVariables',
                'latestDeployment',
                'user:name,email',
            ]),
        ]);
    }

    /**
     * PATCH /api/projects/{project}
     */
    public function update(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $rules = [
            'name' => ['sometimes', 'string', 'max:63'],
            'storage_limit' => ['sometimes', 'integer', 'min:512', 'max:1048576'],
            'memory_limit' => ['sometimes', 'string', 'max:32'],
            'cpu_limit' => ['sometimes', 'integer', 'min:1', 'max:64'],
        ];

        $data = $request->validate($rules);
        $project->update($data);

        AuditService::log('project.updated', "Updated project \"{$project->name}\"", [
            'project_id' => $project->id,
            'changes' => $data,
        ]);

        return response()->json(['project' => $project]);
    }

    /**
     * DELETE /api/projects/{project}
     */
    public function destroy(Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $project->update(['status' => 'deleting']);
        \App\Jobs\DeleteProjectJob::dispatch($project);

        AuditService::log('project.deleted', "Deleted project \"{$project->name}\"", [
            'project_id' => $project->id,
        ]);

        return response()->json(['message' => 'Project deletion started.'], 202);
    }

    /**
     * GET /api/projects/{project}/env
     * Secret values are never returned.
     */
    public function env(Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        return response()->json([
            'environment_variables' => $project->environmentVariables()->get()
                ->map(fn ($v) => [
                    'id' => $v->id,
                    'key' => $v->key,
                    'value' => $v->is_secret ? '********' : $v->value,
                    'is_secret' => $v->is_secret,
                ]),
        ]);
    }

    /**
     * POST /api/projects/{project}/env
     */
    public function storeEnv(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $request->validate([
            'key' => ['required', 'regex:/^[A-Z_][A-Z0-9_]*$/'],
            'value' => ['required', 'string', 'max:1024'],
            'is_secret' => ['sometimes', 'boolean'],
        ]);

        $project->environmentVariables()->updateOrCreate(
            ['key' => $request->input('key')],
            [
                'value' => encrypt($request->input('value')),
                'is_secret' => $request->boolean('is_secret'),
            ]
        );

        return response()->json(['message' => 'Environment variable saved.'], 201);
    }

    /**
     * DELETE /api/projects/{project}/env/{env}
     */
    public function deleteEnv(Request $request, Project $project, $env): JsonResponse
    {
        $this->authorizeProject($project);

        $project->environmentVariables()->whereKey($env)->delete();

        return response()->json(['message' => 'Environment variable deleted.']);
    }

    /**
     * GET /api/projects/{project}/databases
     */
    public function databases(Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        return response()->json([
            'databases' => $project->databases()->get()
                ->map(fn ($d) => [
                    'id' => $d->id,
                    'name' => $d->name,
                    'engine' => $d->engine,
                    'user' => $d->user,
                    'port' => $d->port,
                    // password is never returned.
                ]),
        ]);
    }

    /**
     * POST /api/projects/{project}/databases
     */
    public function createDatabase(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $request->validate([
            'engine' => ['required', 'in:mariadb,mysql,postgresql'],
        ]);

        $db = $project->databases()->create([
            'name' => $project->slug . '_db_' . \Illuminate\Support\Str::random(4),
            'engine' => $request->input('engine'),
            'user' => $project->slug . '_app',
            'password' => encrypt(\Illuminate\Support\Str::random(32)),
            'port' => $request->input('engine') === 'postgresql' ? 5432 : 3306,
        ]);

        return response()->json(['database' => $db], 201);
    }

    private function authorizeProject(Project $project): void
    {
        $user = Auth::user();

        if (! $user->isSuperAdmin() && $project->user_id !== $user->id) {
            abort(403, 'You do not have access to this project.');
        }
    }
}
