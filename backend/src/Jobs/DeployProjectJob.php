<?php

namespace App\Jobs;

use App\Models\Deployment;
use App\Models\Project;
use App\Services\AuditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModel;
use Illuminate\Support\Str;
use Throwable;

class DeployProjectJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModel;

    public int $tries = 4;
    public int $timeout = 600;

    public function __construct(
        public Project $project,
        public ?Deployment $deployment = null,
        public string $action = 'deploy'
    ) {
    }

    public function handle(): void
    {
        $deployment = $this->deployment ?? $this->project->deployments()->create([
            'number' => $this->project->deployments()->max('number') + 1,
            'status' => 'running',
            'command' => $this->action,
        ]);

        $deployment->update(['status' => 'running']);

        try {
            $log = $this->runDeployment();

            $deployment->update([
                'status' => 'success',
                'duration_ms' => $log['duration_ms'],
                'logs' => $log['logs'],
                'commit' => $log['commit'] ?? $deployment->commit,
            ]);

            if ($this->action === 'deploy') {
                $this->project->update(['status' => 'live']);
            }

            AuditService::log('project.deployed', "Deployment #{$deployment->number} succeeded for \"{$this->project->name}\"", [
                'project_id' => $this->project->id,
                'deployment_id' => $deployment->id,
            ]);
        } catch (Throwable $e) {
            $deployment->update([
                'status' => 'failed',
                'logs' => ($deployment->logs ?? '') . "\n[ERROR] " . $e->getMessage(),
            ]);

            AuditService::log('project.deploy_failed', "Deployment #{$deployment->number} failed for \"{$this->project->name}\"", [
                'project_id' => $this->project->id,
                'deployment_id' => $deployment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Build the deployment log. In Phase 1 this is a scripted sequence;
     * Phase 9 streams real agent output (git pull, npm install, build, etc.).
     */
    private function runDeployment(): array
    {
        $lines = [];
        $start = microtime(true);

        $lines[] = "[{time}] Starting {$this->action} for {$this->project->name}";

        if ($this->action === 'deploy' && $this->project->git_repository) {
            $lines[] = "[{time}] git clone {$this->project->git_repository}";
            $lines[] = "[{time}] git checkout {$this->project->git_branch}";
        }

        $lines[] = "[{time}] Install dependencies";
        $lines[] = "[{time}] Build";

        if ($this->project->type === 'laravel') {
            $lines[] = "[{time}] php artisan migrate";
            $lines[] = "[{time}] php artisan storage:link";
        }

        if ($this->action === 'deploy') {
            $lines[] = "[{time}] Restart application";
        }

        $lines[] = "[{time}] Health check: 200 OK";
        $lines[] = "[{time}] Deployment successful";

        $durationMs = (int) round((microtime(true) - $start) * 1000);

        return [
            'logs' => implode("\n", $lines),
            'duration_ms' => $durationMs,
            'commit' => $this->deployment?->commit ?? Str::random(8),
        ];
    }

    /**
     * Dispatch a stop action.
     */
    public static function dispatchStop(Project $project): void
    {
        static::dispatch($project, null, 'stop');
    }

    /**
     * Dispatch a start action.
     */
    public static function dispatchStart(Project $project): void
    {
        static::dispatch($project, null, 'start');
    }

    public function failed(?Throwable $e): void
    {
        AuditService::log('project.deploy_failed', "Deployment failed for \"{$this->project->name}\"", [
            'project_id' => $this->project->id,
            'error' => $e?->getMessage(),
        ]);
    }
}
