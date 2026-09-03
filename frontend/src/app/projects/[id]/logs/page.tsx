'use client';

import { useEffect, useState } from 'react';
import { useParams, useRouter } from 'next/navigation';
import { DashboardLayout } from '@/components/dashboard-layout';
import { api } from '@/lib/api';
import { Textarea } from '@/components/ui/textarea';
import { Button } from '@/components/ui/button';
import { ProtectedRoute } from '@/components/protected-route';

export default function LogsPage() {
  const params = useParams();
  const router = useRouter();
  const id = params.id as string;
  const [logs, setLogs] = useState('');
  const [loading, setLoading] = useState(true);

  async function load() {
    setLoading(true);
    const res = await api.get<{ logs: string }>(`/api/projects/${id}/logs?lines=500`);
    setLogs(res.logs);
    setLoading(false);
  }

  useEffect(() => {
    load();
  }, [id]);

  return (
    <ProtectedRoute>
    <DashboardLayout>
      <div className="flex items-center justify-between mb-6">
        <div>
          <h2 className="text-2xl font-semibold tracking-tight">Logs</h2>
          <p className="text-muted-foreground text-sm">Application and deployment logs (secrets masked).</p>
        </div>
        <Button variant="outline" onClick={() => router.push(`/projects/${id}`)}>
          ← Back
        </Button>
      </div>

      {loading ? (
        <p className="text-muted-foreground">Loading…</p>
      ) : (
        <Textarea readOnly value={logs} className="h-[70vh] text-xs" />
      )}
    </DashboardLayout>
    </ProtectedRoute>
  );
}
