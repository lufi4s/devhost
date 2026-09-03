'use client';

import { useEffect, useState } from 'react';
import { DashboardLayout } from '@/components/dashboard-layout';
import { api } from '@/lib/api';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { Server } from '@/lib/types';
import { ProtectedRoute } from '@/components/protected-route';

export default function ServersPage() {
  const [servers, setServers] = useState<Server[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    api
      .get<{ servers: Server[] }>('/api/admin/servers')
      .then((res) => {
        setServers(res.servers);
        setLoading(false);
      })
      .catch(() => setLoading(false));
  }, []);

  return (
    <ProtectedRoute>
    <DashboardLayout>
      <div className="mb-6">
        <h2 className="text-2xl font-semibold tracking-tight">Servers</h2>
        <p className="text-muted-foreground text-sm">Infrastructure nodes running your workloads.</p>
      </div>

      {loading ? (
        <p className="text-muted-foreground">Loading…</p>
      ) : servers.length > 0 ? (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {servers.map((s) => (
            <Card key={s.id}>
              <CardHeader className="flex flex-row items-center justify-between space-y-0">
                <CardTitle className="text-lg">{s.name}</CardTitle>
                <span
                  className={
                    'rounded-full px-2.5 py-0.5 text-xs font-medium ' +
                    (s.status === 'online'
                      ? 'bg-success/15 text-success'
                      : 'bg-destructive/15 text-destructive')
                  }
                >
                  {s.status}
                </span>
              </CardHeader>
              <CardContent className="text-sm text-muted-foreground space-y-1">
                <p>IP: {s.ip}</p>
                <p>Agent: {s.agent_url}</p>
                <p>Created: {new Date(s.created_at).toLocaleDateString()}</p>
              </CardContent>
            </Card>
          ))}
        </div>
      ) : (
        <p className="text-muted-foreground">No servers configured.</p>
      )}
    </DashboardLayout>
    </ProtectedRoute>
  );
}
