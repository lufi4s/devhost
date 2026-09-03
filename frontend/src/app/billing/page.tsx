'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { DashboardLayout } from '@/components/dashboard-layout';
import { api } from '@/lib/api';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import type { Plan, Subscription, Usage } from '@/lib/types';
import { ProtectedRoute } from '@/components/protected-route';

export default function BillingPage() {
  const router = useRouter();
  const [plans, setPlans] = useState<Plan[]>([]);
  const [subscription, setSubscription] = useState<Subscription | null>(null);
  const [usage, setUsage] = useState<Usage | null>(null);
  const [loading, setLoading] = useState(true);
  const [active, setActive] = useState<string | null>(null);
  const [error, setError] = useState('');

  async function load() {
    try {
      const [plansRes, subRes] = await Promise.all([
        api.get<{ plans: Plan[] }>('/api/billing/plans'),
        api.get<{ subscription?: Subscription; usage?: Usage }>('/api/billing/subscription'),
      ]);
      setPlans(plansRes.plans);
      setSubscription(subRes.subscription ?? null);
      setUsage(subRes.usage ?? null);
    } catch {
      // no subscription yet
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    load();
  }, []);

  async function subscribe(slug: string) {
    setError('');
    setActive(slug);
    try {
      const res = await api.post<{ subscription: Subscription }>('/api/billing/subscribe', { plan_slug: slug });
      setSubscription(res.subscription);
      router.refresh();
    } catch (e) {
      setError((e as { message: string }).message);
    } finally {
      setActive(null);
    }
  }

  function formatPrice(p: number): string {
    return `$${p}`;
  }

  function feature(label: string, enabled: boolean): JSX.Element {
    return (
      <div className="flex items-center gap-2 text-sm">
        <span className={enabled ? 'text-emerald-600' : 'text-muted-foreground'}>
          {enabled ? '✓' : '—'}
        </span>
        {label}
      </div>
    );
  }

  return (
    <ProtectedRoute>
    <DashboardLayout>
      <div className="mb-6">
        <h2 className="text-2xl font-semibold tracking-tight">Billing & Plans</h2>
        <p className="text-muted-foreground text-sm">
          Choose a hosting plan. Limits are enforced automatically on your account.
        </p>
      </div>

      {error && <p className="mb-4 text-sm text-destructive">{error}</p>}

      {subscription && usage && (
        <Card className="mb-8">
          <CardHeader>
            <CardTitle className="text-lg">Current subscription</CardTitle>
            <CardDescription>{subscription.plan?.name} · {subscription.billing_cycle}</CardDescription>
          </CardHeader>
          <CardContent className="grid gap-4 sm:grid-cols-3">
            <div>
              <div className="text-xs uppercase text-muted-foreground">Status</div>
              <div className="font-medium capitalize">{subscription.status}</div>
            </div>
            <div>
              <div className="text-xs uppercase text-muted-foreground">Websites</div>
              <div className="font-medium">{usage.websites.used}/{usage.websites.limit}</div>
            </div>
            <div>
              <div className="text-xs uppercase text-muted-foreground">Databases</div>
              <div className="font-medium">{usage.databases.used}/{usage.databases.limit}</div>
            </div>
            <div>
              <div className="text-xs uppercase text-muted-foreground">Mailboxes</div>
              <div className="font-medium">{usage.mailboxes.used}/{usage.mailboxes.limit}</div>
            </div>
            <div>
              <div className="text-xs uppercase text-muted-foreground">Amount</div>
              <div className="font-medium">{formatPrice(subscription.amount)}</div>
            </div>
            <div>
              <div className="text-xs uppercase text-muted-foreground">Renews</div>
              <div className="font-medium">{subscription.current_period_end ? new Date(subscription.current_period_end).toLocaleDateString() : '—'}</div>
            </div>
          </CardContent>
        </Card>
      )}

      <div className="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
        {plans.map((plan) => (
          <Card key={plan.id} className="flex flex-col">
            <CardHeader>
              <CardTitle className="text-lg">{plan.name}</CardTitle>
              <CardDescription>{formatPrice(plan.price)}/{plan.billing_cycle}</CardDescription>
            </CardHeader>
            <CardContent className="flex flex-1 flex-col gap-4">
              <ul className="grid gap-2">
                <li className="text-sm">{plan.storage_limit >= 1024 ? `${(plan.storage_limit / 1024).toFixed(0)} GB` : `${plan.storage_limit} MB`} storage</li>
                <li className="text-sm">{plan.memory_limit >= 1024 ? `${(plan.memory_limit / 1024).toFixed(0)} GB` : `${plan.memory_limit} MB`} RAM</li>
                <li className="text-sm">{plan.cpu_limit} CPU cores</li>
                <li className="text-sm">{plan.bandwidth_limit} GB bandwidth</li>
                <li className="text-sm">{plan.websites_limit} websites</li>
                <li className="text-sm">{plan.databases_limit} databases</li>
                <li className="text-sm">{plan.mailboxes_limit} mailboxes</li>
              </ul>
              <div className="grid grid-cols-2 gap-x-4 gap-y-1.5 border-t border-border pt-3">
                {feature('WordPress', plan.wordpress_enabled)}
                {feature('Laravel', plan.laravel_enabled)}
                {feature('Node.js', plan.node_enabled)}
                {feature('Static', plan.static_enabled)}
                {feature('PHP', plan.php_enabled)}
                {feature('Redis', plan.redis_enabled)}
                {feature('Backups', plan.backup_enabled)}
                {feature('SFTP', plan.sftp_enabled)}
              </div>
              <Button
                className="mt-auto"
                onClick={() => subscribe(plan.slug)}
                disabled={active === plan.slug || (subscription?.plan?.slug === plan.slug)}
              >
                {subscription?.plan?.slug === plan.slug ? 'Current plan' : active === plan.slug ? 'Subscribing…' : 'Subscribe'}
              </Button>
            </CardContent>
          </Card>
        ))}
      </div>
    </DashboardLayout>
    </ProtectedRoute>
  );
}
