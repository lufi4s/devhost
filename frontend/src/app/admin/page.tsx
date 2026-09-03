'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { DashboardLayout } from '@/components/dashboard-layout';
import { api } from '@/lib/api';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ProtectedRoute } from '@/components/protected-route';
import type { Paginated, Project, User } from '@/lib/types';
import { Users, FolderGit2, Server, FileText } from 'lucide-react';

export default function AdminIndexPage() {
  const [users, setUsers] = useState<User[]>([]);
  const [projects, setProjects] = useState<Project[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    async function load() {
      const [usersRes, projectsRes] = await Promise.all([
        api.get<{ data: User[] }>('/api/admin/users'),
        api.get<Paginated<Project>>('/api/admin/projects?per_page=1'),
      ]);
      setUsers(usersRes.data);
      setProjects(projectsRes.data ?? []);
      setLoading(false);
    }
    load();
  }, []);

  const cards = [
    { href: '/admin/users', label: 'Users', description: 'Manage platform users and roles', icon: Users },
    { href: '/admin/projects', label: 'Projects', description: 'All projects across the platform', icon: FolderGit2 },
    { href: '/admin/servers', label: 'Servers', description: 'Infrastructure nodes', icon: Server },
    { href: '/admin/audit-logs', label: 'Audit logs', description: 'Immutable action record', icon: FileText },
  ];

  return (
    <ProtectedRoute>
    <DashboardLayout>
      <div className="mb-6">
        <h2 className="text-2xl font-semibold tracking-tight">Admin</h2>
        <p className="text-muted-foreground text-sm">Platform administration and oversight.</p>
      </div>

      {loading ? (
        <p className="text-muted-foreground">Loading…</p>
      ) : (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          {cards.map((c) => {
            const Icon = c.icon;
            return (
              <Link key={c.href} href={c.href}>
                <Card className="hover:bg-muted/40 transition-colors h-full">
                  <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium text-muted-foreground">{c.label}</CardTitle>
                    <Icon className="h-4 w-4 text-muted-foreground" />
                  </CardHeader>
                  <CardContent>
                    <p className="text-sm text-muted-foreground">{c.description}</p>
                  </CardContent>
                </Card>
              </Link>
            );
          })}
        </div>
      )}
    </DashboardLayout>
    </ProtectedRoute>
  );
}
