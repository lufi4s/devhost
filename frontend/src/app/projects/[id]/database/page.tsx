'use client';

import { useEffect, useState } from 'react';
import { useParams, useRouter } from 'next/navigation';
import { DashboardLayout } from '@/components/dashboard-layout';
import { api } from '@/lib/api';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import type { ProjectDatabase } from '@/lib/types';
import { ProtectedRoute } from '@/components/protected-route';

export default function DatabasePage() {
  const params = useParams();
  const router = useRouter();
  const id = params.id as string;
  const [databases, setDatabases] = useState<ProjectDatabase[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    api
      .get<{ databases: ProjectDatabase[] }>(`/api/projects/${id}/databases`)
      .then((res) => {
        setDatabases(res.databases);
        setLoading(false);
      })
      .catch(() => setLoading(false));
  }, [id]);

  return (
    <ProtectedRoute>
    <DashboardLayout>
      <div className="flex items-center justify-between mb-6">
        <div>
          <h2 className="text-2xl font-semibold tracking-tight">Database</h2>
          <p className="text-muted-foreground text-sm">Automatically provisioned databases.</p>
        </div>
        <Button variant="outline" onClick={() => router.push(`/projects/${id}`)}>
          ← Back
        </Button>
      </div>

      {loading ? (
        <p className="text-muted-foreground">Loading…</p>
      ) : databases.length > 0 ? (
        <div className="space-y-3">
          {databases.map((d) => (
            <Card key={d.id}>
              <CardHeader className="flex flex-row items-center justify-between space-y-0">
                <CardTitle className="text-lg">{d.name}</CardTitle>
                <span className="rounded-full bg-primary/10 text-primary px-2.5 py-0.5 text-xs font-medium">
                  {d.engine}
                </span>
              </CardHeader>
              <CardContent>
                <p className="text-sm text-muted-foreground">
                  User: <span className="font-mono">{d.user}</span> · Port: {d.port}
                </p>
              </CardContent>
            </Card>
          ))}
        </div>
      ) : (
        <p className="text-muted-foreground">No databases for this project.</p>
      )}
    </DashboardLayout>
    </ProtectedRoute>
  );
}
