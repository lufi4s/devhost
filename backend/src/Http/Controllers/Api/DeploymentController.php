<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deployment;
use App\Models\Project;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DeploymentController extends Controller
{
    /**
     * GET /api/projects/{project}/deployments
     */
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        return response()->json($project->deployments()
            ->paginate($request->integer('per_page', 15)));
    }

    /**
     * POST /api/projects/{project}/deploy
     *
     * Predefined action only. There is NO arbitrary shell execution.
     */
    public function deploy(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $request->validate([
            'commit' => ['nullable', 'string', 'max:64', 'regex:/^[0-9a-fA-F]{7,40}$/'],
        ]);

        $deployment = $project->deployments()->create([
            'number' => $project->deployments()->max('number') + 1,
            'status' => 'pending',
            'command' => 'deploy',
            'commit' => $request->input('commit'),
        ]);

        \App\Jobs\DeployProjectJob::dispatch($project, $deployment);

        return response()->json(['deployment' => $deployment], 202);
    }

    /**
     * POST /api/projects/{project}/restart
     */
    public function restart(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $deployment = $project->deployments()->create([
            'number' => $project->deployments()->max('number') + 1,
            'status' => 'pending',
            'command' => 'restart',
        ]);

        \App\Jobs\DeployProjectJob::dispatch($project, $deployment);

        return response()->json(['deployment' => $deployment], 202);
    }

    /**
     * POST /api/projects/{project}/stop
     */
    public function stop(Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $project->update(['status' => 'stopped']);
        \App\Jobs\DeployProjectJob::dispatchStop($project);

        AuditService::log('project.stopped', "Stopped project \"{$project->name}\"", [
            'project_id' => $project->id,
        ]);

        return response()->json(['project' => $project]);
    }

    /**
     * POST /api/projects/{project}/start
     */
    public function start(Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $project->update(['status' => 'live']);
        \App\Jobs\DeployProjectJob::dispatchStart($project);

        AuditService::log('project.started', "Started project \"{$project->name}\"", [
            'project_id' => $project->id,
        ]);

        return response()->json(['project' => $project]);
    }

    /**
     * GET /api/projects/{project}/logs
     * Secrets are masked before being returned.
     */
    public function logs(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $lines = $request->integer('lines', 200);
        // Phase 9: stream from the agent (docker logs, nginx access/error).
        // Phase 1: return the last N deployment log lines, masked.
        $logs = $project->deployments()
            ->whereNotNull('logs')
            ->latest('created_at')
            ->limit($lines)
            ->get()
            ->pluck('logs')
            ->implode("\n");

        return response()->json([
            'logs' => $this->maskSecrets($logs),
        ]);
    }

    /**
     * GET /api/projects/{project}/metrics
     */
    public function metrics(Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        // Phase 9: pull from the agent / Prometheus.
        // Phase 1: static placeholder reflecting container status.
        return response()->json([
            'cpu_percent' => 0,
            'memory_mb' => 0,
            'storage_mb' => 0,
            'container_status' => $project->status,
            'http_status' => $project->isLive() ? 200 : 0,
            'response_time_ms' => 0,
            'db_available' => $project->databases()->count() > 0,
        ]);
    }

    private function authorizeProject(Project $project): void
    {
        $user = Auth::user();

        if (! $user->isSuperAdmin() && $project->user_id !== $user->id) {
            abort(403, 'You do not have access to this project.');
        }
    }

    /**
     * Mask common secret patterns in log output.
     */
    private function maskSecrets(string $text): string
    {
        $patterns = ['PASSWORD', 'TOKEN', 'API_KEY', 'SECRET'];

        foreach ($patterns as $keyword) {
            $text = preg_replace_callback(
                '/(' . $keyword . '\s*[:=]\s*)(\S+)/i',
                fn ($m) => $m[1] . str_repeat('*', strlen($m[2])),
                $text
            );
        }

        return $text;
    }
}
