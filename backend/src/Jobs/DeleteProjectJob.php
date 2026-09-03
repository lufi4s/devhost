<?php

namespace App\Jobs;

use App\Models\Project;
use App\Services\AuditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModel;
use Throwable;

class DeleteProjectJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModel;

    public int $tries = 8;
    public int $timeout = 300;

    public function __construct(public Project $project)
    {
    }

    public function handle(): void
    {
        // Phase 9: stop + remove Docker network, containers, volumes, DNS, SSL.
        // Phase 1: remove DB records.

        $this->project->domains()->delete();
        $this->project->databases()->delete();
        $this->project->environmentVariables()->delete();
        $this->project->delete();

        AuditService::log('project.deleted', "Project \"{$this->project->name}\" fully removed", [
            'project_id' => $this->project->id,
        ]);
    }

    public function failed(?Throwable $e): void
    {
        AuditService::log('project.delete_failed', "Delete failed for \"{$this->project->name}\"", [
            'project_id' => $this->project->id,
            'error' => $e?->getMessage(),
        ]);
    }
}
