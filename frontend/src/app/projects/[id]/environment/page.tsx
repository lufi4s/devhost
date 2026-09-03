'use client';

import { useEffect, useState } from 'react';
import { useParams, useRouter } from 'next/navigation';
import { DashboardLayout } from '@/components/dashboard-layout';
import { api } from '@/lib/api';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ProtectedRoute } from '@/components/protected-route';

interface EnvVar {
  id: number;
  key: string;
  value: string;
  is_secret: boolean;
}

export default function EnvironmentPage() {
  const params = useParams();
  const router = useRouter();
  const id = params.id as string;
  const [vars, setVars] = useState<EnvVar[]>([]);
  const [loading, setLoading] = useState(true);
  const [newKey, setNewKey] = useState('');
  const [newValue, setNewValue] = useState('');
  const [secret, setSecret] = useState(false);
  const [saving, setSaving] = useState(false);

  async function load() {
    setLoading(true);
    const res = await api.get<{ environment_variables: EnvVar[] }>(
      `/api/projects/${id}/env`
    );
    setVars(res.environment_variables);
    setLoading(false);
  }

  useEffect(() => {
    load();
  }, [id]);

  async function add() {
    if (!newKey) return;
    setSaving(true);
    await api.post(`/api/projects/${id}/env`, {
      key: newKey,
      value: newValue,
      is_secret: secret,
    });
    setNewKey('');
    setNewValue('');
    setSecret(false);
    await load();
    setSaving(false);
  }

  async function remove(env: EnvVar) {
    await api.del(`/api/projects/${id}/env/${env.id}`);
    await load();
  }

  return (
    <ProtectedRoute>
    <DashboardLayout>
      <div className="flex items-center justify-between mb-6">
        <div>
          <h2 className="text-2xl font-semibold tracking-tight">Environment</h2>
          <p className="text-muted-foreground text-sm">
            Environment variables. Secrets are encrypted at rest and never shown.
          </p>
        </div>
        <Button variant="outline" onClick={() => router.push(`/projects/${id}`)}>
          ← Back
        </Button>
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle className="text-lg">Variables</CardTitle>
          </CardHeader>
          <CardContent>
            {loading ? (
              <p className="text-muted-foreground">Loading…</p>
            ) : vars.length > 0 ? (
              <ul className="space-y-2">
                {vars.map((v) => (
                  <li
                    key={v.id}
                    className="flex items-center justify-between rounded-md border border-border px-3 py-2"
                  >
                    <div>
                      <span className="font-mono text-sm">{v.key}</span>
                      <span className="ml-2 rounded bg-muted px-1.5 py-0.5 text-[10px] uppercase">
                        {v.is_secret ? 'secret' : 'public'}
                      </span>
                    </div>
                    <Button variant="ghost" size="sm" onClick={() => remove(v)}>
                      Delete
                    </Button>
                  </li>
                ))}
              </ul>
            ) : (
              <p className="text-muted-foreground">No environment variables.</p>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle className="text-lg">Add variable</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid gap-1.5">
              <Label htmlFor="key">Key</Label>
              <Input
                id="key"
                value={newKey}
                onChange={(e) => setNewKey(e.target.value.toUpperCase())}
                placeholder="APP_ENV"
              />
            </div>
            <div className="grid gap-1.5">
              <Label htmlFor="value">Value</Label>
              <Textarea
                id="value"
                value={newValue}
                onChange={(e) => setNewValue(e.target.value)}
                placeholder="production"
              />
            </div>
            <label className="flex items-center gap-2 text-sm">
              <input
                type="checkbox"
                checked={secret}
                onChange={(e) => setSecret(e.target.checked)}
              />
              Mark as secret
            </label>
            <Button className="w-full" onClick={add} disabled={saving || !newKey}>
              {saving ? 'Saving…' : 'Add variable'}
            </Button>
          </CardContent>
        </Card>
      </div>
    </DashboardLayout>
    </ProtectedRoute>
  );
}
