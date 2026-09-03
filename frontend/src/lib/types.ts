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
