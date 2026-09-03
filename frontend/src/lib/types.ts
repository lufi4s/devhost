export type ProjectType = 'wordpress' | 'laravel' | 'static' | 'node';

export type ProjectStatus =
  | 'provisioning'
  | 'live'
  | 'stopped'
  | 'failed'
  | 'provisioning_failed'
  | 'deleting'
  | 'suspended';

export interface User {
  id: number;
  name: string;
  email: string;
  role?: {
    id: number;
    name: string;
    slug: string;
  };
}

export interface Server {
  id: number;
  name: string;
  ip: string;
  agent_url: string;
  status: string;
  created_at: string;
}

export interface AuditLog {
  id: number;
  user_id: number | null;
  user_name?: string;
  action: string;
  description: string;
  context?: Record<string, unknown>;
  created_at: string;
}

export interface ProjectUser {
  id: number;
  name: string;
  email: string;
}

export interface Project {
  id: number;
  user_id: number;
  name: string;
  slug: string;
  type: ProjectType;
  status: ProjectStatus;
  runtime: string | null;
  runtime_version: string | null;
  subdomain: string;
  domain: string;
  hostname: string;
  git_repository: string | null;
  git_branch: string | null;
  storage_limit: number;
  memory_limit: string;
  cpu_limit: number;
  created_at: string;
  domains?: ProjectDomain[];
  databases?: ProjectDatabase[];
  latestDeployment?: Deployment | null;
  user?: ProjectUser;
}

export interface ProjectDomain {
  id: number;
  hostname: string;
  type: string;
  ssl_status: string;
}

export interface ProjectDatabase {
  id: number;
  name: string;
  engine: string;
  user: string;
  port: number;
}

export interface Deployment {
  id: number;
  number: number;
  status: string;
  command: string;
  commit: string | null;
  duration_ms: number | null;
  logs: string | null;
  created_at: string;
}

export interface Paginated<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  links?: {
    url: string | null;
    label: string;
    active: boolean;
  }[];
}

export interface LoginPayload {
  email: string;
  password: string;
}

export interface Plan {
  id: number;
  name: string;
  slug: string;
  currency: string;
  price: number;
  billing_cycle: string;
  storage_limit: number;
  memory_limit: number;
  cpu_limit: number;
  bandwidth_limit: number;
  websites_limit: number;
  databases_limit: number;
  mailboxes_limit: number;
  domains_limit: number;
  email_storage: number;
  node_enabled: boolean;
  laravel_enabled: boolean;
  wordpress_enabled: boolean;
  php_enabled: boolean;
  static_enabled: boolean;
  backup_enabled: boolean;
  sftp_enabled: boolean;
  redis_enabled: boolean;
  ssl_auto: boolean;
  is_active: boolean;
  sort_order: number;
}

export interface Subscription {
  id: number;
  customer_id: number;
  plan_id: number;
  status: string;
  billing_cycle: string;
  amount: number;
  current_period_end: string | null;
  plan?: Plan;
}

export interface Usage {
  websites: { allowed: boolean; used: number; limit: number };
  databases: { allowed: boolean; used: number; limit: number };
  mailboxes: { allowed: boolean; used: number; limit: number };
}

export interface CustomerDomain {
  id: number;
  name: string;
  verified: boolean;
  primary: boolean;
  nameserver_managed: boolean;
  nameserver_1?: string | null;
  nameserver_2?: string | null;
  ssl_status: string;
  dns_records?: DnsRecord[];
  created_at: string;
}

export interface DnsRecord {
  id: number;
  name: string;
  type: string;
  value: string;
  ttl: number;
  priority?: number | null;
}

export interface CreateProjectPayload {
  name: string;
  subdomain: string;
  domain: string;
  type: ProjectType;
  php_version?: string;
  node_version?: string;
  database?: string;
  git_repository?: string;
  git_branch?: string;
}
