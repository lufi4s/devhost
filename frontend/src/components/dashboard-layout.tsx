'use client';

import Link from 'next/link';
import { usePathname, useRouter } from 'next/navigation';
import { useAuth } from '@/hooks/useAuth';
import { cn } from '@/lib/utils';
import { LayoutDashboard, Server, Settings, LogOut, FolderGit2, Shield, CreditCard, Globe } from 'lucide-react';

const navItems = [
  { href: '/dashboard', label: 'Dashboard', icon: LayoutDashboard },
  { href: '/projects', label: 'Projects', icon: FolderGit2 },
  { href: '/domains', label: 'Domains', icon: Globe },
  { href: '/servers', label: 'Servers', icon: Server },
  { href: '/billing', label: 'Billing', icon: CreditCard },
  { href: '/backups', label: 'Backups', icon: Server },
  { href: '/settings', label: 'Settings', icon: Settings },
];

const adminItems = [
  { href: '/admin', label: 'Admin', icon: Shield },
];

export function DashboardLayout({ children }: { children: React.ReactNode }) {
  const router = useRouter();
  const { user, logout } = useAuth();
  const pathname = usePathname();

  const isAdmin = user?.role?.slug === 'super_admin' || user?.role?.slug === 'admin';

  async function handleLogout() {
    await logout();
    router.push('/login');
  }

  return (
    <div className="flex min-h-screen bg-background">
      <aside className="fixed inset-y-0 left-0 w-64 border-r border-background-muted flex flex-col bg-card">
        <div className="h-14 flex items-center px-5 border-b border-border">
          <span className="font-semibold tracking-tight">DevHost</span>
        </div>
        <nav className="flex-1 p-3 space-y-1">
          {navItems.map((item) => {
            const Icon = item.icon;
            const active = pathname === item.href;
            return (
              <Link
                key={item.href}
                href={item.href}
                className={cn(
                  'flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                  active ? 'bg-accent text-accent-foreground' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'
                )}
              >
                <Icon className="h-4 w-4" />
                {item.label}
              </Link>
            );
          })}
          {isAdmin && (
            <>
              <div className="my-3 border-t border-border" />
              {adminItems.map((item) => {
                const Icon = item.icon;
                const active = pathname === item.href;
                return (
                  <Link
                    key={item.href}
                    href={item.href}
                    className={cn(
                      'flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                      active ? 'bg-accent text-accent-foreground' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'
                    )}
                  >
                    <Icon className="h-4 w-4" />
                    {item.label}
                  </Link>
                );
              })}
            </>
          )}
        </nav>
        <div className="p-3 border-t border-border">
          <button
            onClick={handleLogout}
            className="flex w-full items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-muted-foreground hover:bg-accent hover:text-accent-foreground"
          >
            <LogOut className="h-4 w-4" />
            Log out
          </button>
        </div>
      </aside>
      <main className="flex-1 pl-64">
        <div className="h-14 border-b border-border flex items-center px-6">
          <h1 className="font-medium tracking-tight">{titleFor(pathname)}</h1>
        </div>
        <div className="p-6">{children}</div>
      </main>
    </div>
  );
}

function titleFor(pathname: string): string {
  const map: Record<string, string> = {
    '/dashboard': 'Dashboard',
    '/projects': 'Projects',
    '/domains': 'Domains',
    '/servers': 'Servers',
    '/billing': 'Billing',
    '/backups': 'Backups',
    '/settings': 'Settings',
    '/admin': 'Admin',
  };
  return map[pathname] ?? 'DevHost';
}
