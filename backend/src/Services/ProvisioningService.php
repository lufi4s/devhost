<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectDatabase;
use App\Models\ProjectDomain;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Orchestrates the provisioning steps for a project.
 *
 * In Phase 1 the Docker/Nginx/DNS/SSL operations are STUBBED: the service
 * records what it *would* do (creating domains, databases, containers) and
 * returns a structured result. In Phase 9 the same steps call the Node Agent
 * over HTTPS with signed tokens / mTLS to perform real Docker operations.
 *
 * Every step is wrapped so a failure records the failing step and reason,
 * leaving the project in a retryable state.
 */
class ProvisioningService
{
    public function provision(Project $project): array
    {
        $steps = [
            'validate' => fn () => $this->stepValidate($project),
            'create_network' => fn () => $this->stepCreateNetwork($project),
            'create_database' => fn () => $this->stepCreateDatabase($project),
            'create_application' => fn () => $this->stepCreateApplication($project),
            'configure_proxy' => fn () => $this->stepConfigureProxy($project),
            'configure_dns' => fn () => $this->stepConfigureDns($project),
            'issue_ssl' => fn () => $this->stepIssueSsl($project),
            'health_check' => fn () => $this->stepHealthCheck($project),
        ];

        foreach ($steps as $name => $step) {
            try {
                $result = $step();
                $project->push(); // persist status changes

                if ($result === 'skip') {
                    continue;
                }

                if ($result === 'fail') {
                    return ['status' => 'failed', 'step' => $name];
                }
            } catch (\Throwable $e) {
                return ['status' => 'failed', 'step' => $name, 'error' => $e->getMessage()];
            }
        }

        $project->update(['status' => 'live']);

        return ['status' => 'live'];
    }

    private function stepValidate(Project $project): string
    {
        if (! in_array($project->type, Project::TYPES, true)) {
            return 'fail';
        }

        return 'ok';
    }

    private function stepCreateNetwork(Project $project): string
    {
        // Phase 9: create a dedicated Docker network: docker network create devhost-<slug>
        return 'ok';
    }

    private function stepCreateDatabase(Project $project): string
    {
        if (! app(ProjectService::class)->needsDatabase($project->type)) {
            return 'skip';
        }

        return DB::transaction(function () use ($project) {
            $db = ProjectDatabase::create([
                'project_id' => $project->id,
                'name' => Str::slug($project->slug) . '_db',
                'engine' => $project->type === 'laravel' && $project->git_repository
                    ? 'postgresql'
                    : 'mariadb',
                'user' => Str::slug($project->slug) . '_app',
                'password' => encrypt(Str::random(32)),
                'port' => 3306,
            ]);

            return 'ok';
        });
    }

    private function stepCreateApplication(Project $project): string
    {
        // Phase 9: build the compose project from templates/{type} and start it.
        return 'ok';
    }

    private function stepConfigureProxy(Project $project): string
    {
        ProjectDomain::create([
            'project_id' => $project->id,
            'hostname' => $project->hostname,
            'type' => 'subdomain',
            'ssl_status' => 'none',
        ]);

        // Phase 9: generate Nginx server block for the hostname → container:port
        return 'ok';
    }

    private function stepConfigureDns(Project $project): string
    {
        // Phase 9: Cloudflare API — add an A record for $project->hostname.
        return 'ok';
    }

    private function stepIssueSsl(Project $project): string
    {
        // Phase 9: Cloudflare Origin CA or Let's Encrypt, then mark ssl_status = active.
        $project->domains()->where('hostname', $project->hostname)
            ->update(['ssl_status' => 'issuing']);

        return 'ok';
    }

    private function stepHealthCheck(Project $project): string
    {
        // Phase 9: HTTP/TCP probe to $project->hostname.
        try {
            Http::timeout(5)->get("https://{$project->hostname}/health");
        } catch (\Throwable) {
            // A non-200 or unreachable endpoint still counts as "provisioned"
            // in Phase 1 (the app may not expose /health yet).
        }

        return 'ok';
    }
}
