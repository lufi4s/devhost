'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { DashboardLayout } from '@/components/dashboard-layout';
import { api } from '@/lib/api';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectOption } from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import type { CreateProjectPayload, ProjectType } from '@/lib/types';
import { ProtectedRoute } from '@/components/protected-route';

const TYPES: { value: ProjectType; label: string; description: string }[] = [
  { value: 'wordpress', label: 'WordPress', description: 'PHP-FPM + MariaDB + Redis + WP-CLI' },
  { value: 'laravel', label: 'Laravel', description: 'PHP + Composer + queue + scheduler' },
  { value: 'node', label: 'Node.js', description: 'Node 20/22 with npm build & start' },
  { value: 'static', label: 'Static', description: 'HTML/CSS/JS via Nginx' },
];

export default function CreateProjectPage() {
  const router = useRouter();
  const [type, setType] = useState<ProjectType>('node');
  const [name, setName] = useState('');
  const [subdomain, setSubdomain] = useState('');
  const [domain, setDomain] = useState('dev.example.com');
  const [nodeVersion, setNodeVersion] = useState('22');
  const [phpVersion, setPhpVersion] = useState('8.3');
  const [gitRepo, setGitRepo] = useState('');
  const [gitBranch, setGitBranch] = useState('main');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  async function onSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError('');
    setLoading(true);

    const payload: CreateProjectPayload = {
      name,
      subdomain,
      domain,
      type,
      node_version: type === 'node' ? nodeVersion : undefined,
      php_version: type === 'wordpress' || type === 'laravel' ? phpVersion : undefined,
      git_repository: gitRepo || undefined,
      git_branch: gitBranch || undefined,
    };

    try {
      await api.post<{ project: { id: number } }>('/api/projects', payload);
      router.push('/projects');
    } catch (err) {
      const e = err as { message: string; errors?: Record<string, string[]> };
      if (e.errors) {
        const first = Object.values(e.errors)[0];
        setError(first?.[0] ?? 'Validation failed');
      } else {
        setError(e.message ?? 'Failed to create project');
      }
      setLoading(false);
    }
  }

  return (
    <ProtectedRoute>
    <DashboardLayout>
      <div className="mb-6">
        <h2 className="text-2xl font-semibold tracking-tight">Create project</h2>
        <p className="text-muted-foreground text-sm">
          Choose an application type and a subdomain. The platform provisions everything else.
        </p>
      </div>

      <form onSubmit={onSubmit} className="grid gap-6 lg:grid-cols-3">
        <div className="lg:col-span-2 space-y-3">
          <div className="grid gap-3 sm:grid-cols-2">
            {TYPES.map((t) => (
              <button
                type="button"
                key={t.value}
                onClick={() => setType(t.value)}
                className={
                  'text-left rounded-lg border p-4 transition-colors ' +
                  (type === t.value
                    ? 'border-primary bg-accent'
                    : 'border-border hover:bg-muted/40')
                }
              >
                <span className="block font-medium">{t.label}</span>
                <span className="block text-xs text-muted-foreground mt-1">{t.description}</span>
              </button>
            ))}
          </div>

          <div className="rounded-lg border border-border p-4 space-y-4">
            <div className="grid gap-1.5">
              <Label htmlFor="name">Project name</Label>
              <Input
                id="name"
                value={name}
                onChange={(e) => setName(e.target.value)}
                required
                placeholder="my-project"
              />
            </div>

            <div className="grid gap-1.5">
              <Label htmlFor="subdomain">Subdomain</Label>
              <div className="flex items-center">
                <Input
                  id="subdomain"
                  value={subdomain}
                  onChange={(e) => setSubdomain(e.target.value)}
                  required
                  placeholder="my-project"
                />
                <span className="ml-2 shrink-0 text-sm text-muted-foreground whitespace-nowrap">
                  .{domain}
                </span>
              </div>
            </div>

            <div className="grid gap-1.5">
              <Label htmlFor="domain">Base domain</Label>
              <Input
                id="domain"
                value={domain}
                onChange={(e) => setDomain(e.target.value)}
                required
                placeholder="dev.example.com"
              />
            </div>

            {type === 'node' && (
              <div className="grid gap-1.5">
                <Label htmlFor="nodeVersion">Node.js version</Label>
                <Select value={nodeVersion} onChange={setNodeVersion}>
                  <SelectOption value="20">Node.js 20</SelectOption>
                  <SelectOption value="22">Node.js 22</SelectOption>
                  <SelectOption value="latest">Node.js latest LTS</SelectOption>
                </Select>
              </div>
            )}

            {(type === 'wordpress' || type === 'laravel') && (
              <div className="grid gap-1.5">
                <Label htmlFor="phpVersion">PHP version</Label>
                <Select value={phpVersion} onChange={setPhpVersion}>
                  <SelectOption value="8.2">PHP 8.2</SelectOption>
                  <SelectOption value="8.3">PHP 8.3</SelectOption>
                  <SelectOption value="8.4">PHP 8.4</SelectOption>
                </Select>
              </div>
            )}

            <div className="grid gap-1.5">
              <Label htmlFor="gitRepo">Git repository (optional)</Label>
              <Input
                id="gitRepo"
                value={gitRepo}
                onChange={(e) => setGitRepo(e.target.value)}
                placeholder="git@example.com:project.git"
              />
            </div>

            <div className="grid gap-1.5">
              <Label htmlFor="gitBranch">Branch</Label>
              <Input
                id="gitBranch"
                value={gitBranch}
                onChange={(e) => setGitBranch(e.target.value)}
                placeholder="main"
              />
            </div>
          </div>
        </div>

        <div className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle className="text-lg">Review</CardTitle>
              <CardDescription>What will be provisioned</CardDescription>
            </CardHeader>
            <CardContent className="space-y-2 text-sm">
              <div className="flex justify-between">
                <span className="text-muted-foreground">Type</span>
                <span className="font-medium capitalize">{type}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-muted-foreground">Hostname</span>
                <span className="font-mono text-xs">
                  {subdomain || '—'}.{domain || '…'}
                </span>
              </div>
              <div className="flex justify-between">
                <span className="text-muted-foreground">Storage</span>
                <span className="font-medium">20 GB</span>
              </div>
              <div className="flex justify-between">
                <span className="text-muted-foreground">Memory</span>
                <span className="font-medium">2 GB</span>
              </div>
              <div className="flex justify-between">
                <span className="text-muted-foreground">CPU</span>
                <span className="font-medium">2 cores</span>
              </div>
            </CardContent>
          </Card>

          {error && <p className="text-sm text-destructive">{error}</p>}

          <Button type="submit" className="w-full" disabled={loading}>
            {loading ? 'Creating…' : 'Create project'}
          </Button>
        </div>
      </form>
    </DashboardLayout>
    </ProtectedRoute>
  );
}
