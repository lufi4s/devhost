'use client';

import { useParams, useRouter } from 'next/navigation';
import { DashboardLayout } from '@/components/dashboard-layout';
import { Button } from '@/components/ui/button';
import { ProtectedRoute } from '@/components/protected-route';

export default function BackupsPage() {
  const params = useParams();
  const router = useRouter();
  const id = params.id as string;

  return (
    <ProtectedRoute>
    <DashboardLayout>
      <div className="flex items-center justify-between mb-6">
        <div>
          <h2 className="text-2xl font-semibold tracking-tight">Backups</h2>
          <p className="text-muted-foreground text-sm">
            Automated backups and point-in-time recovery.
          </p>
        </div>
        <Button variant="outline" onClick={() => router.push(`/projects/${id}`)}>
          ← Back
        </Button>
      </div>

      <div className="rounded-lg border border-border border-dashed p-12 text-center">
        <p className="text-muted-foreground">Backups are not available in Phase 1.</p>
        <p className="text-sm text-muted-foreground mt-1">
          Automated backups will be provisioned in a later phase.
        </p>
      </div>
    </DashboardLayout>
    </ProtectedRoute>
  );
}
