'use client';

import { DashboardLayout } from '@/components/dashboard-layout';
import { ProtectedRoute } from '@/components/protected-route';

export default function SettingsPage() {
  return (
    <ProtectedRoute>
    <DashboardLayout>
      <div className="mb-6">
        <h2 className="text-2xl font-semibold tracking-tight">Settings</h2>
        <p className="text-muted-foreground text-sm">Platform configuration.</p>
      </div>

      <div className="rounded-lg border border-border border-dashed p-12 text-center">
        <p className="text-muted-foreground">Settings are not available in Phase 1.</p>
      </div>
    </DashboardLayout>
    </ProtectedRoute>
  );
}
