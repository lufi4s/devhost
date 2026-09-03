'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { DashboardLayout } from '@/components/dashboard-layout';
import { api } from '@/lib/api';
import { StatusBadge } from '@/components/ui/status-badge';
import type { Paginated, Project } from '@/lib/types';
import { Plus, Search } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { ProtectedRoute } from '@/components/protected-route';

export default function ProjectsPage() {
  const [projects, setProjects] = useState<Project[]>([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [meta, setMeta] = useState<{ last_page: number }>({ last_page: 1 });

  async function load(p = 1) {
    setLoading(true);
    const res = await api.get<Paginated<Project>>(
      `/api/projects?per_page=10&page=${p}`
    );
    setProjects(res.data);
    setMeta({ last_page: res.last_page });
    setPage(res.current_page);
    setLoading(false);
  }

  useEffect(() => {
    load();
  }, []);

  return (
    <ProtectedRoute>
    <DashboardLayout>
      <div className="flex items-center justify-between mb-6">
        <div>
          <h2 className="text-2xl font-semibold tracking-tight">Projects</h2>
          <p className="text-muted-foreground text-sm">Manage and monitor your hosted projects.</p>
        </div>
        <Link href="/projects/create">
          <Button>
            <Plus className="h-4 w-4" />
            New project
          </Button>
        </Link>
      </div>

      <div className="relative mb-4">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input className="pl-9" placeholder="Search projects…" />
      </div>

      {loading ? (
        <p className="text-muted-foreground">Loading…</p>
      ) : (
        <div className="rounded-lg border border-border overflow-hidden">
          <table className="w-full text-sm">
            <thead className="bg-muted/40 text-muted-foreground">
              <tr>
                <th className="text-left px-4 py-2 font-medium">Name</th>
                <th className="text-left px-4 py-2 font-medium">Type</th>
                <th className="text-left px-4 py-2 font-medium">Status</th>
                <th className="text-left px-4 py-2 font-medium">URL</th>
                <th className="text-left px-4 py-2 font-medium">Runtime</th>
              </tr>
            </thead>
            <tbody>
              {projects.map((p) => (
                <tr
                  key={p.id}
                  className="border-t border-border hover:bg-muted/30 cursor-pointer"
                  onClick={() => window.location.assign(`/projects/${p.id}`)}
                >
                  <td className="px-4 py-3 font-medium">{p.name}</td>
                  <td className="px-4 py-3 uppercase text-muted-foreground text-xs">{p.type}</td>
                  <td className="px-4 py-3"><StatusBadge status={p.status} /></td>
                  <td className="px-4 py-3 font-mono text-xs text-muted-foreground">{p.hostname}</td>
                  <td className="px-4 py-3 text-xs">
                    {p.runtime} {p.runtime_version}
                  </td>
                </tr>
              ))}
              {projects.length === 0 && (
                <tr>
                  <td colSpan={5} className="px-4 py-8 text-center text-muted-foreground">
                    No projects found.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      )}

      {meta.last_page > 1 && (
        <div className="flex items-center justify-center gap-3 mt-4">
          <Button variant="outline" disabled={page <= 1} onClick={() => load(page - 1)}>
            Previous
          </Button>
          <span className="text-sm text-muted-foreground">
            Page {page} of {meta.last_page}
          </span>
          <Button variant="outline" disabled={page >= meta.last_page} onClick={() => load(page + 1)}>
            Next
          </Button>
        </div>
      )}
    </DashboardLayout>
    </ProtectedRoute>
  );
}
