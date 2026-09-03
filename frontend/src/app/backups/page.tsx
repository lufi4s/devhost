'use client';

import { DashboardLayout } from '@/components/dashboard-layout';
import { ProtectedRoute } from '@/components/protected-route';

export default function BackupsPage() {
  return (
    <ProtectedRoute>
    <DashboardLayout>
      <div className="mb-6">
        <h2 className="text-2xl font-semibold tracking-tight">Backups</h2>
        <p className="text-muted-foreground text-sm">Global backup configuration.</p>
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
