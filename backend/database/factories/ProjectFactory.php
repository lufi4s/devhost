<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        $type = fake()->randomElement(Project::TYPES);

        return [
            'user_id' => User::factory(),
            'name' => fake()->word(),
            'slug' => Str::slug(fake()->word()) . '-' . Str::random(6),
            'type' => $type,
            'status' => 'live',
            'runtime' => $type === 'node' ? 'node' : ($type === 'static' ? 'nginx' : 'php'),
            'runtime_version' => '8.3',
            'subdomain' => fake()->unique()->safeEmail(),
            'domain' => 'dev.example.com',
            'hostname' => fake()->unique()->safeEmail(),
            'git_repository' => null,
            'git_branch' => 'main',
            'storage_limit' => 20480,
            'memory_limit' => '2048m',
            'cpu_limit' => 2,
        ];
    }
}
