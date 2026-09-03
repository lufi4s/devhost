import { cn } from '@/lib/utils';
import type { ProjectStatus } from '@/lib/types';

const statusConfig: Record<
  ProjectStatus,
  { label: string; className: string }
> = {
  live: { label: 'LIVE', className: 'bg-success/15 text-success' },
  provisioning: { label: 'PROVISIONING', className: 'bg-primary/10 text-primary' },
  stopped: { label: 'STOPPED', className: 'bg-muted text-muted-foreground' },
  failed: { label: 'FAILED', className: 'bg-destructive/15 text-destructive' },
  provisioning_failed: { label: 'PROVISIONING FAILED', className: 'bg-destructive/15 text-destructive' },
  deleting: { label: 'DELETING', className: 'bg-muted text-muted-foreground' },
  suspended: { label: 'SUSPENDED', className: 'bg-muted text-muted-foreground' },
};

export function StatusBadge({ status }: { status: ProjectStatus }) {
  const config = statusConfig[status] ?? statusConfig.stopped;

  return (
    <span
      className={cn(
        'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium',
        config.className
      )}
    >
      <span className={cn('h-1.5 w-1.5 rounded-full', config.className)} />
      {config.label}
    </span>
  );
}
