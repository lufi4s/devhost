'use client';

import { useEffect, useState } from 'react';
import { DashboardLayout } from '@/components/dashboard-layout';
import { api } from '@/lib/api';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectOption } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type { CustomerDomain, DnsRecord } from '@/lib/types';
import { ProtectedRoute } from '@/components/protected-route';

export default function DomainsPage() {
  const [domains, setDomains] = useState<CustomerDomain[]>([]);
  const [usage, setUsage] = useState({ used: 0, limit: 0 });
  const [loading, setLoading] = useState(true);
  const [name, setName] = useState('');
  const [error, setError] = useState('');
  const [saving, setSaving] = useState(false);

  // Per-domain DNS record draft.
  const [recordDomainId, setRecordDomainId] = useState<number | null>(null);
  const [recName, setRecName] = useState('');
  const [recType, setRecType] = useState('A');
  const [recValue, setRecValue] = useState('');

  async function load() {
    try {
      const [domainsRes, subRes] = await Promise.all([
        api.get<{ domains: CustomerDomain[] }>('/api/domains'),
        api.get<{ subscription?: { usage?: { domains?: { used: number; limit: number } } } }>('/api/billing/subscription'),
      ]);
      setDomains(domainsRes.domains);
      const used = subRes.subscription?.usage?.domains;
      if (used) setUsage(used);
    } catch {
      // subscription may not exist yet
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    load();
  }, []);

  async function addDomain(e: React.FormEvent) {
    e.preventDefault();
    setError('');
    setSaving(true);
    try {
      const res = await api.post<{ domain: CustomerDomain; records: unknown[] }>('/api/domains', { name });
      setDomains((prev) => [res.domain, ...prev]);
      setName('');
      await load();
    } catch (e) {
      setError((e as { message: string }).message);
    } finally {
      setSaving(false);
    }
  }

  async function setPrimary(d: CustomerDomain) {
    try {
      await api.post(`/api/domains/${d.id}/set-primary`);
      await load();
    } catch (e) {
      setError((e as { message: string }).message);
    }
  }

  async function configureManaged(d: CustomerDomain) {
    try {
      const res = await api.post<{ domain: CustomerDomain; records: unknown[] }>(
        `/api/domains/${d.id}/configure-managed`,
      );
      setDomains((prev) => prev.map((x) => (x.id === d.id ? res.domain : x)));
    } catch (e) {
      setError((e as { message: string }).message);
    }
  }

  async function removeDomain(d: CustomerDomain) {
    if (! confirm(`Remove ${d.name}? This deletes its DNS records too.`)) return;
    try {
      await api.del(`/api/domains/${d.id}`);
      setDomains((prev) => prev.filter((x) => x.id !== d.id));
      await load();
    } catch (e) {
      setError((e as { message: string }).message);
    }
  }

  async function addRecord(d: CustomerDomain) {
    if (! recName || ! recValue) return;
    try {
      const res = await api.post<{ record: DnsRecord }>(`/api/domains/${d.id}/dns-records`, {
        name: recName,
        type: recType,
        value: recValue,
      });
      setDomains((prev) =>
        prev.map((x) => {
          if (x.id !== d.id) return x;
          return { ...x, dns_records: [...(x.dns_records ?? []), res.record] };
        })
      );
      setRecName('');
      setRecValue('');
    } catch (e) {
      setError((e as { message: string }).message);
    }
  }

  async function deleteRecord(d: CustomerDomain, r: DnsRecord) {
    try {
      await api.del(`/api/domains/${d.id}/dns-records/${r.id}`);
      setDomains((prev) =>
        prev.map((x) => {
          if (x.id !== d.id) return x;
          return { ...x, dns_records: (x.dns_records ?? []).filter((y) => y.id !== r.id) };
        })
      );
    } catch (e) {
      setError((e as { message: string }).message);
    }
  }

  const recordDomain = domains.find((d) => d.id === recordDomainId);

  return (
    <ProtectedRoute>
    <DashboardLayout>
      <div className="mb-6">
        <h2 className="text-2xl font-semibold tracking-tight">Domains</h2>
        <p className="text-muted-foreground text-sm">
          Add your domains. Automatic DNS, SSL and routing are handled once you use the platform nameservers.
        </p>
      </div>

      {error && <p className="mb-4 text-sm text-destructive">{error}</p>}

      <Card className="mb-8">
        <CardHeader>
          <CardTitle className="text-lg">Add a domain</CardTitle>
          <CardDescription>
            Use the platform nameservers for fully automatic DNS. Limit:{' '}
            {usage.limit === 0 ? 'unlimited' : `${usage.used}/${usage.limit}`}
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form onSubmit={addDomain} className="flex flex-wrap items-end gap-3">
            <div className="grid gap-1.5 flex-1 min-w-[16rem]">
              <Label htmlFor="domain">Domain or subdomain</Label>
              <Input
                id="domain"
                value={name}
                onChange={(e) => setName(e.target.value)}
                placeholder="example.com"
              />
            </div>
            <Button type="submit" disabled={saving || !name}>
              {saving ? 'Adding…' : 'Add domain'}
            </Button>
          </form>
        </CardContent>
      </Card>

      {loading ? (
        <p className="text-muted-foreground">Loading…</p>
      ) : domains.length === 0 ? (
        <p className="text-muted-foreground">No domains yet.</p>
      ) : (
        <div className="space-y-4">
          {domains.map((d) => (
            <Card key={d.id}>
              <CardHeader className="flex flex-row items-start justify-between space-y-0">
                <div>
                  <CardTitle className="text-lg flex items-center gap-2">
                    {d.name}
                    {d.primary && (
                      <span className="rounded-full bg-primary/10 text-primary px-2 py-0.5 text-xs font-medium">
                        Primary
                      </span>
                    )}
                  </CardTitle>
                  <CardDescription>{d.verified ? 'Domain verified' : 'Pending verification'}</CardDescription>
                </div>
                <div className="flex items-center gap-2">
                  {!d.primary && (
                    <Button variant="outline" size="sm" onClick={() => setPrimary(d)}>
                      Set primary
                    </Button>
                  )}
                  <Button variant="destructive" size="sm" onClick={() => removeDomain(d)}>
                    Remove
                  </Button>
                </div>
              </CardHeader>
              <CardContent className="space-y-4">
                {!d.nameserver_managed ? (
                  <div className="rounded-md border border-border bg-muted/30 p-3 text-sm space-y-3">
                    <div>
                      <p className="font-medium mb-2">Use these nameservers at your registrar:</p>
                      <div className="font-mono text-muted-foreground">ns1.yourplatform.com</div>
                      <div className="font-mono text-muted-foreground">ns2.yourplatform.com</div>
                    </div>
                    <Button
                      variant="outline"
                      size="sm"
                      className="w-full"
                      onClick={() => configureManaged(d)}
                    >
                      Set up automatic DNS
                    </Button>
                  </div>
                ) : (
                  <div className="rounded-md border border-success/30 bg-success/5 p-3 text-sm text-success">
                    Managed automatically — DNS records are served by our nameservers.
                  </div>
                )}

                <div>
                  <h3 className="text-sm font-medium mb-2">DNS records</h3>
                  {d.dns_records && d.dns_records.length > 0 ? (
                    <div className="overflow-x-auto">
                      <table className="text-sm w-full">
                        <thead>
                          <tr className="border-b border-border text-muted-foreground text-left">
                            <th className="py-2 pr-4 font-medium">Name</th>
                            <th className="py-2 pr-4 font-medium">Type</th>
                            <th className="py-2 pr-4 font-medium">Value</th>
                            <th className="py-2 font-medium">TTL</th>
                            <th />
                          </tr>
                        </thead>
                        <tbody>
                          {d.dns_records.map((r) => (
                            <tr key={r.id} className="border-b border-border/50">
                              <td className="py-2 font-mono">{r.name}</td>
                              <td className="py-2">{r.type}</td>
                              <td className="py-2 font-mono break-all">{r.value}</td>
                              <td className="py-2">{r.ttl}</td>
                              <td className="py-2 text-right">
                                <button
                                  className="text-xs text-destructive hover:underline"
                                  onClick={() => deleteRecord(d, r)}
                                >
                                  Delete
                                </button>
                              </td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  ) : (
                    <p className="text-sm text-muted-foreground">No DNS records.</p>
                  )}

                  {recordDomainId === d.id ? (
                    <div className="grid gap-2 mt-3 sm:grid-cols-5 items-end">
                      <div>
                        <Label htmlFor="rec-name">Name</Label>
                        <Input id="rec-name" value={recName} onChange={(e) => setRecName(e.target.value)} placeholder="@" />
                      </div>
                      <div>
                        <Label htmlFor="rec-type">Type</Label>
                        <Select value={recType} onChange={setRecType}>
                          <SelectOption value="A">A</SelectOption>
                          <SelectOption value="AAAA">AAAA</SelectOption>
                          <SelectOption value="CNAME">CNAME</SelectOption>
                          <SelectOption value="MX">MX</SelectOption>
                          <SelectOption value="TXT">TXT</SelectOption>
                          <SelectOption value="CAA">CAA</SelectOption>
                        </Select>
                      </div>
                      <div className="sm:col-span-3">
                        <Label htmlFor="rec-value">Value</Label>
                        <Textarea id="rec-value" value={recValue} onChange={(e) => setRecValue(e.target.value)} placeholder="0.0.0.0" />
                      </div>
                      <Button onClick={() => addRecord(d)}>Add record</Button>
                    </div>
                  ) : (
                    <Button variant="outline" size="sm" className="mt-3" onClick={() => setRecordDomainId(d.id)}>
                      Add DNS record
                    </Button>
                  )}
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}
    </DashboardLayout>
    </ProtectedRoute>
  );
}
