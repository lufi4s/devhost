<?php

namespace App\Jobs;

use App\Models\Project;
use App\Services\AuditService;
use App\Services\ProvisioningService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModel;
use Throwable;

class ProvisionProjectJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModel;

    public int $tries = 16;
    public int $timeout = 300;

    public function __construct(public Project $project)
    {
    }

    public function handle(ProvisioningService $provisioning): void
    {
        $result = $provisioning->provision($this->project);

        if ($result['status'] === 'failed') {
            $this->project->update([
                'status' => 'provisioning_failed',
            ]);

            AuditService::log('project.provision_failed', "Provisioning failed for \"{$this->project->name}\"", [
                'project_id' => $this->project->id,
                'step' => $result['step'] ?? null,
                'error' => $result['error'] ?? null,
            ]);

            throw new \RuntimeException("Provisioning failed at step: {$result['step']}");
        }

        AuditService::log('project.provisioned', "Project \"{$this->project->name}\" is now LIVE", [
            'project_id' => $this->project->id,
        ]);
    }

    public function failed(?Throwable $e): void
    {
        AuditService::log('project.provision_failed', "Provisioning failed for \"{$this->project->name}\"", [
            'project_id' => $this->project->id,
            'error' => $e?->getMessage(),
        ]);
    }
}
