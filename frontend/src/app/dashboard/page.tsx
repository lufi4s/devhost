'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { DashboardLayout } from '@/components/dashboard-layout';
import { api } from '@/lib/api';
import { cn } from '@/lib/utils';
import type { Paginated, Project, User } from '@/lib/types';
import { FolderGit2, Server, CheckCircle2, AlertCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { ProtectedRoute } from '@/components/protected-route';

export default function DashboardPage() {
  const [user, setUser] = useState<User | null>(null);
  const [projects, setProjects] = useState<Project[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    async function load() {
      const [userRes, projectsRes] = await Promise.all([
        api.get<{ user: User }>('/api/auth/me'),
        api.get<Paginated<Project>>('/api/projects?per_page=6'),
      ]);
      setUser(userRes.user);
      setProjects(projectsRes.data);
      setLoading(false);
    }
    load();
  }, []);

  const liveCount = projects.filter((p) => p.status === 'live').length;

  const stats = [
    { label: 'Projects', value: projects.length, icon: FolderGit2, tone: 'text-primary' },
    { label: 'Running', value: liveCount, icon: CheckCircle2, tone: 'text-success' },
    { label: 'Servers', value: '—', icon: Server, tone: 'text-muted-foreground' },
    { label: 'Failed', value: projects.filter((p) => p.status === 'provisioning_failed').length, icon: AlertCircle, tone: 'text-destructive' },
  ];

  return (
    <ProtectedRoute>
    <DashboardLayout>
      {loading ? (
        <p className="text-muted-foreground">Loading…</p>
      ) : (
        <div className="space-y-6">
          <div className="flex items-center justify-between">
            <div>
              <h2 className="text-2xl font-semibold tracking-tight">Welcome back, {user?.name ?? 'Developer'}</h2>
              <p className="text-muted-foreground text-sm">Here&apos;s what&apos;s running across your projects.</p>
            </div>
            <Link href="/projects/create">
              <Button>
                <FolderGit2 className="h-4 w-4" />
                New project
              </Button>
            </Link>
          </div>

          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {stats.map((s) => {
              const Icon = s.icon;
              return (
                <div key={s.label} className="rounded-lg border border-border bg-card p-5">
                  <div className="flex items-center justify-between">
                    <span className="text-sm font-medium text-muted-foreground">{s.label}</span>
                    <Icon className={cn('h-4 w-4', s.tone)} />
                  </div>
                  <p className="mt-2 text-3xl font-semibold tracking-tight">{s.value}</p>
                </div>
              );
            })}
          </div>

          <div>
            <h3 className="text-lg font-semibold tracking-tight mb-3">Recent projects</h3>
            <div className="rounded-lg border border-border overflow-hidden">
              <table className="w-full text-sm">
                <thead className="bg-muted/40 text-muted-foreground">
                  <tr>
                    <th className="text-left px-4 py-2 font-medium">Name</th>
                    <th className="text-left px-4 py-2 font-medium">Type</th>
                    <th className="text-left px-4 py-2 font-medium">Status</th>
                    <th className="text-left px-4 py-2 font-medium">URL</th>
                  </tr>
                </thead>
                <tbody>
                  {projects.map((p) => (
                    <tr key={p.id} className="border-t border-border hover:bg-muted/30">
                      <td className="px-4 py-3 font-medium">{p.name}</td>
                      <td className="px-4 py-4 uppercase text-muted-foreground text-xs">{p.type}</td>
                      <td className="px-4 py-3">
                        <span className="rounded-full bg-muted px-2 py-0.5 text-xs font-medium">
                          {p.status.replace(/_/g, ' ')}
                        </span>
                      </td>
                      <td className="px-4 py-3 font-mono text-xs text-muted-foreground">{p.hostname}</td>
                    </tr>
                  ))}
                  {projects.length === 0 && (
                    <tr>
                      <td colSpan={4} className="px-4 py-8 text-center text-muted-foreground">
                        No projects yet.
                      </td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}
    </DashboardLayout>
    </ProtectedRoute>
  );
}
