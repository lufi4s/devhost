'use client';

import { useEffect, useState } from 'react';
import { useParams, useRouter } from 'next/navigation';
import { DashboardLayout } from '@/components/dashboard-layout';
import { api } from '@/lib/api';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import type { Deployment } from '@/lib/types';
import { Play, RotateCcw } from 'lucide-react';
import { ProtectedRoute } from '@/components/protected-route';

export default function DeploymentsPage() {
  const params = useParams();
  const router = useRouter();
  const id = params.id as string;

  const [deployments, setDeployments] = useState<Deployment[]>([]);
  const [loading, setLoading] = useState(true);
  const [deploying, setDeploying] = useState(false);

  async function load() {
    setLoading(true);
    const res = await api.get<{ data: Deployment[] }>(`/api/projects/${id}/deployments?per_page=20`);
    setDeployments(res.data ?? []);
    setLoading(false);
  }

  useEffect(() => {
    load();
  }, [id]);

  async function deploy() {
    setDeploying(true);
    try {
      await api.post(`/api/projects/${id}/deploy`);
      await load();
    } finally {
      setDeploying(false);
    }
  }

  return (
    <ProtectedRoute>
    <DashboardLayout>
      <div className="flex items-center justify-between mb-6">
        <div>
          <h2 className="text-2xl font-semibold tracking-tight">Deployments</h2>
          <p className="text-muted-foreground text-sm">Deployment history and actions.</p>
        </div>
        <div className="flex gap-2">
          <Button variant="outline" onClick={() => router.push(`/projects/${id}`)}>
            ← Back
          </Button>
          <Button onClick={deploy} disabled={deploying}>
            <Play className="h-4 w-4" /> Deploy now
          </Button>
        </div>
      </div>

      {loading ? (
        <p className="text-muted-foreground">Loading…</p>
      ) : (
        <div className="space-y-3">
          {deployments.map((d) => (
            <Card key={d.id}>
              <CardHeader className="flex flex-row items-center justify-between space-y-0">
                <CardTitle className="text-lg">
                  Deployment #{d.number} · <span className="capitalize">{d.command}</span>
                </CardTitle>
                <span
                  className={
                    'rounded-full px-2.5 py-0.5 text-xs font-medium ' +
                    (d.status === 'success'
                      ? 'bg-success/15 text-success'
                      : d.status === 'failed'
                      ? 'bg-destructive/15 text-destructive'
                      : 'bg-primary/10 text-primary')
                  }
                >
                  {d.status}
                </span>
              </CardHeader>
              <CardContent>
                <p className="text-xs text-muted-foreground mb-2">
                  {new Date(d.created_at).toLocaleString()} ·{' '}
                  {d.commit ? `commit ${d.commit}` : '—'} ·{' '}
                  {d.duration_ms ? `${d.duration_ms}ms` : '—'}
                </p>
                <Textarea
                  readOnly
                  value={d.logs ?? ''}
                  className="h-48 resize-y text-xs"
                />
              </CardContent>
            </Card>
          ))}
          {deployments.length === 0 && (
            <p className="text-muted-foreground">No deployments yet.</p>
          )}
        </div>
      )}
    </DashboardLayout>
    </ProtectedRoute>
  );
}
