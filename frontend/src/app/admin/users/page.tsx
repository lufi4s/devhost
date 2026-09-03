'use client';

import { useEffect, useState } from 'react';
import { DashboardLayout } from '@/components/dashboard-layout';
import { api } from '@/lib/api';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { User } from '@/lib/types';
import { ProtectedRoute } from '@/components/protected-route';

export default function AdminUsersPage() {
  const [users, setUsers] = useState<User[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  async function load() {
    setLoading(true);
    try {
      const res = await api.get<{ data: User[] }>('/api/admin/users');
      setUsers(res.data);
    } catch (e) {
      setError((e as { message: string }).message);
    }
    setLoading(false);
  }

  useEffect(() => {
    load();
  }, []);

  async function toggleRole(user: User) {
    const next = user.role?.slug === 'developer' ? 'admin' : 'developer';
    await api.patch(`/api/admin/users/${user.id}`, { role: next });
    await load();
  }

  async function remove(user: User) {
    await api.del(`/api/admin/users/${user.id}`);
    await load();
  }

  return (
    <ProtectedRoute>
    <DashboardLayout>
      <div className="mb-6">
        <h2 className="text-2xl font-semibold tracking-tight">Users</h2>
        <p className="text-muted-foreground text-sm">Manage platform users and roles.</p>
      </div>

      {error && <p className="text-destructive text-sm mb-4">{error}</p>}

      {loading ? (
        <p className="text-muted-foreground">Loading…</p>
      ) : (
        <div className="rounded-lg border border-border overflow-hidden">
          <table className="w-full text-sm">
            <thead className="bg-muted/40 text-muted-foreground">
              <tr>
                <th className="text-left px-4 py-2 font-medium">Name</th>
                <th className="text-left px-4 py-2 font-medium">Email</th>
                <th className="text-left px-4 py-2 font-medium">Roles</th>
                <th className="text-right px-4 py-2 font-medium">Actions</th>
              </tr>
            </thead>
            <tbody>
              {users.map((u) => (
                <tr key={u.id} className="border-t border-border">
                  <td className="px-4 py-3">{u.name}</td>
                  <td className="px-4 py-3">{u.email}</td>
                  <td className="px-4 py-3">
                    {u.role ? (
                      <span className="inline-block rounded bg-primary/10 text-primary px-2 py-0.5 text-xs capitalize">
                        {u.role.slug}
                      </span>
                    ) : (
                      <span className="text-muted-foreground">—</span>
                    )}
                  </td>
                  <td className="px-4 py-3 text-right">
                    <Button variant="ghost" size="sm" onClick={() => toggleRole(u)}>
                      Grant admin
                    </Button>
                    <Button variant="ghost" size="sm" onClick={() => remove(u)}>
                      Delete
                    </Button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </DashboardLayout>
    </ProtectedRoute>
  );
}
