'use client';

import { useEffect, useState } from 'react';
import { useParams, useRouter } from 'next/navigation';
import { DashboardLayout } from '@/components/dashboard-layout';
import { api } from '@/lib/api';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import type { ProjectDomain } from '@/lib/types';
import { ProtectedRoute } from '@/components/protected-route';

export default function DomainsPage() {
  const params = useParams();
  const router = useRouter();
  const id = params.id as string;
  const [domains, setDomains] = useState<ProjectDomain[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    api
      .get<{ domains: ProjectDomain[] }>(`/api/projects/${id}/domains`)
      .then((res) => {
        setDomains(res.domains);
        setLoading(false);
      })
      .catch(() => setLoading(false));
  }, [id]);

  return (
    <ProtectedRoute>
    <DashboardLayout>
      <div className="flex items-center justify-between mb-6">
        <div>
          <h2 className="text-2xl font-semibold tracking-tight">Domains</h2>
          <p className="text-muted-foreground text-sm">
            Managed domains with automatic DNS and TLS.
          </p>
        </div>
        <Button variant="outline" onClick={() => router.push(`/projects/${id}`)}>
          ← Back
        </Button>
      </div>

      {loading ? (
        <p className="text-muted-foreground">Loading…</p>
      ) : domains.length > 0 ? (
        <div className="space-y-3">
          {domains.map((d) => (
            <Card key={d.id}>
              <CardHeader className="flex flex-row items-center justify-between space-y-0">
                <CardTitle className="text-lg font-mono">{d.hostname}</CardTitle>
                <span
                  className={
                    'rounded-full px-2.5 py-0.5 text-xs font-medium ' +
                    (d.ssl_status === 'active'
                      ? 'bg-success/15 text-success'
                      : d.ssl_status === 'issuing'
                      ? 'bg-primary/10 text-primary'
                      : 'bg-muted text-muted-foreground')
                  }
                >
                  {d.ssl_status === 'active' ? 'TLS active' : d.ssl_status}
                </span>
              </CardHeader>
              <CardContent className="text-sm text-muted-foreground">
                {d.type === 'subdomain' ? 'Subdomain' : 'Custom domain'}
              </CardContent>
            </Card>
          ))}
        </div>
      ) : (
        <p className="text-muted-foreground">No domains configured.</p>
      )}
    </DashboardLayout>
    </ProtectedRoute>
  );
}
