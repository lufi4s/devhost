'use client';

import { useEffect, useState } from 'react';
import { useParams, useRouter } from 'next/navigation';
import { DashboardLayout } from '@/components/dashboard-layout';
import { api } from '@/lib/api';
import { StatusBadge } from '@/components/ui/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import type { Project } from '@/lib/types';
import { Activity, Cpu, HardDrive, RefreshCw, Server, Square, Play } from 'lucide-react';
import { ProtectedRoute } from '@/components/protected-route';

export default function ProjectDetailPage() {
  const params = useParams();
  const router = useRouter();
  const id = params.id as string;

  const [project, setProject] = useState<Project | null>(null);
  const [loading, setLoading] = useState(true);
  const [action, setAction] = useState('');

  async function load() {
    setLoading(true);
    const res = await api.get<{ project: Project }>(`/api/projects/${id}`);
    setProject(res.project);
    setLoading(false);
  }

  useEffect(() => {
    load();
  }, [id]);

  async function runAction(fn: () => Promise<void>, label: string) {
    setAction(label);
    try {
      await fn();
      await load();
    } finally {
      setAction('');
    }
  }

  if (loading) {
    return (
      <ProtectedRoute>
        <DashboardLayout>
          <p className="text-muted-foreground">Loading…</p>
        </DashboardLayout>
      </ProtectedRoute>
    );
  }

  if (!project) {
    return (
      <DashboardLayout>
        <p className="text-muted-foreground">Project not found.</p>
      </DashboardLayout>
    );
  }

  const metrics = [
    { label: 'CPU', value: '12%', icon: Cpu },
    { label: 'RAM', value: '384 MB', icon: Activity },
    { label: 'Storage', value: '2.1 GB', icon: HardDrive },
    { label: 'Container', value: project.status, icon: Server },
  ];

  return (
    <ProtectedRoute>
      <DashboardLayout>
        <div className="flex items-start justify-between mb-6">
          <div>
            <div className="flex items-center gap-3">
              <h2 className="text-2xl font-semibold tracking-tight">{project.name}</h2>
              <StatusBadge status={project.status} />
            </div>
            <p className="font-mono text-sm text-muted-foreground mt-1">{project.hostname}</p>
          </div>
          <div className="flex items-center gap-2">
            <Button
              variant="outline"
              size="sm"
              disabled={!!action}
              onClick={() => runAction(() => api.post(`/api/projects/${id}/restart`), 'restart')}
            >
              <RefreshCw className="h-4 w-4" /> Restart
            </Button>
            <Button
              variant="outline"
              size="sm"
              disabled={!!action}
              onClick={() => runAction(() => api.post(`/api/projects/${id}/stop`), 'stop')}
            >
              <Square className="h-4 w-4" /> Stop
            </Button>
            <Button
              variant="outline"
              size="sm"
              disabled={!!action}
              onClick={() => runAction(() => api.post(`/api/projects/${id}/start`), 'start')}
            >
              <Play className="h-4 w-4" /> Start
            </Button>
          </div>
        </div>

        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
          {metrics.map((m) => {
            const Icon = m.icon;
            return (
              <Card key={m.label}>
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                  <CardTitle className="text-sm font-medium text-muted-foreground">{m.label}</CardTitle>
                  <Icon className="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                  <div className="text-2xl font-semibold tracking-tight">{m.value}</div>
                </CardContent>
              </Card>
            );
          })}
        </div>

        <div className="grid gap-4 lg:grid-cols-3">
          <Card className="lg:col-span-2">
            <CardHeader>
              <CardTitle className="text-lg">Overview</CardTitle>
            </CardHeader>
            <CardContent>
              <dl className="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                <div className="flex justify-between border-b border-border pb-2">
                  <dt className="text-muted-foreground">Type</dt>
                  <dd className="font-medium capitalize">{project.type}</dd>
                </div>
                <div className="flex justify-between border-b border-border pb-2">
                  <dt className="text-muted-foreground">Runtime</dt>
                  <dd className="font-medium">
                    {project.runtime} {project.runtime_version}
                  </dd>
                </div>
                <div className="flex justify-between border-b border-border pb-2">
                  <dt className="text-muted-foreground">Storage limit</dt>
                  <dd className="font-medium">{(project.storage_limit / 1024).toFixed(0)} GB</dd>
                </div>
                <div className="flex justify-between border-b border-border pb-2">
                  <dt className="text-muted-foreground">Memory limit</dt>
                  <dd className="font-medium">{project.memory_limit}</dd>
                </div>
                <div className="flex justify-between border-b border-border pb-2">
                  <dt className="text-muted-foreground">CPU limit</dt>
                  <dd className="font-medium">{project.cpu_limit} cores</dd>
                </div>
                <div className="flex justify-between border-b border-border pb-2">
                  <dt className="text-muted-foreground">Git branch</dt>
                  <dd className="font-medium font-mono">{project.git_branch ?? '—'}</dd>
                </div>
              </dl>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle className="text-lg">Databases</CardTitle>
              <CardDescription>Automatically provisioned databases</CardDescription>
            </CardHeader>
            <CardContent>
              {project.databases && project.databases.length > 0 ? (
                <ul className="space-y-2 text-sm">
                  {project.databases.map((d) => (
                    <li key={d.id} className="rounded-md border border-border p-3">
                      <div className="font-medium">{d.name}</div>
                      <div className="text-xs text-muted-foreground">
                        {d.engine} · user: {d.user}
                      </div>
                    </li>
                  ))}
                </ul>
              ) : (
                <p className="text-sm text-muted-foreground">No databases.</p>
              )}
            </CardContent>
          </Card>
        </div>

        <div className="mt-6 flex gap-2">
          <Button onClick={() => router.push(`/projects/${id}/deployments`)}>Deployments</Button>
          <Button variant="outline" onClick={() => router.push(`/projects/${id}/logs`)}>Logs</Button>
          <Button variant="outline" onClick={() => router.push(`/projects/${id}/files`)}>Files</Button>
          <Button variant="outline" onClick={() => router.push(`/projects/${id}/database`)}>Database</Button>
          <Button variant="outline" onClick={() => router.push(`/projects/${id}/environment`)}>Environment</Button>
          <Button variant="outline" onClick={() => router.push(`/projects/${id}/domains`)}>Domains</Button>
          <Button variant="outline" onClick={() => router.push(`/projects/${id}/backups`)}>Backups</Button>
          <Button variant="outline" onClick={() => router.push(`/projects/${id}/settings`)}>Settings</Button>
        </div>
      </DashboardLayout>
    </ProtectedRoute>
  );
}
