<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Str;

/**
 * Shared validation + slug/hostname logic used by the controller and jobs.
 * Kept free of any Docker/Nginx/DNS side effects so it is safe to unit test.
 */
class ProjectService
{
    /**
     * Validate and normalize the incoming create-project payload.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>  normalized attributes
     */
    public function validateCreate(array $input): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:63', 'regex:/^[a-zA-Z0-9 _-]+$/'],
            'subdomain' => ['required', 'string', 'max:63', 'regex:/^[a-z0-9][a-z0-9-]*$/'],
            'domain' => ['required', 'string', 'max:253', 'regex:/^[a-z0-9][a-z0-9-]*\.[a-z]{2,}$/'],
            'type' => ['required', 'in:wordpress,laravel,static,node'],
            'php_version' => ['nullable', 'string', 'max:16'],
            'node_version' => ['nullable', 'string', 'max:16'],
            'database' => ['nullable', 'in:mariadb,mysql,postgresql'],
            'git_repository' => ['nullable', 'string', 'max:512'],
            'git_branch' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9._\/-]+$/'],
            'storage_limit' => ['nullable', 'integer', 'min:512', 'max:1048576'],
            'memory_limit' => ['nullable', 'string', 'max:32'],
            'cpu_limit' => ['nullable', 'integer', 'min:1', 'max:64'],
        ];

        $data = validator($input, $rules)->validate();

        return $this->normalize($data);
    }

    /**
     * Normalize raw input into project attributes (slugs, hostname, defaults).
     */
    public function normalize(array $data): array
    {
        $subdomain = strtolower((string) $data['subdomain']);
        $domain = strtolower((string) $data['domain']);
        $hostname = "{$subdomain}.{$domain}";
        $slug = Str::slug((string) $data['name']) . '-' . Str::random(6);

        $type = $data['type'];

        // Runtime defaults per type.
        $runtime = match ($type) {
            'wordpress' => 'php',
            'laravel' => 'php',
            'node' => 'node',
            'static' => 'nginx',
        };
        $runtimeVersion = match ($type) {
            'wordpress' => $data['php_version'] ?? '8.3',
            'laravel' => $data['php_version'] ?? '8.3',
            'node' => $data['node_version'] ?? '22',
            'static' => '1.27',
        };

        return [
            'name' => trim((string) $data['name']),
            'slug' => $slug,
            'type' => $type,
            'subdomain' => $subdomain,
            'domain' => $domain,
            'hostname' => $hostname,
            'runtime' => $runtime,
            'runtime_version' => $runtimeVersion,
            'git_repository' => $data['git_repository'] ?? null,
            'git_branch' => $data['git_branch'] ?? 'main',
            'storage_limit' => $data['storage_limit'] ?? 20480,
            'memory_limit' => $data['memory_limit'] ?? '2048m',
            'cpu_limit' => $data['cpu_limit'] ?? 2,
        ];
    }

    /**
     * Check whether a hostname is already taken (excluding a given project id).
     */
    public function hostnameExists(string $hostname, ?int $exceptId = null): bool
    {
        $query = Project::where('hostname', $hostname);
        if ($exceptId !== null) {
            $query->whereKeyNot($exceptId);
        }

        return $query->exists();
    }

    /**
     * Whether a project type needs a database.
     */
    public function needsDatabase(string $type): bool
    {
        return in_array($type, ['wordpress', 'laravel'], true);
    }
}
