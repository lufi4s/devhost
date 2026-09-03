# Database schema (Phase 1)

PostgreSQL via Laravel migrations. Full list of tables for the whole platform is at the
bottom; this document details the Phase 1 tables.

## Phase 1 tables

### `users`
| Column        | Type          | Notes                          |
|---------------|---------------|--------------------------------|
| id            | bigint PK     |                                |
| name          | string        |                                |
| email         | string unique |                                |
| password      | string        | hashed                         |
| role_id       | bigint FK     | → roles.id                     |
| two_factor_secret | text      | optional                       |
| two_factor_enabled | bool     | default false                  |
| remember_token| string        |                                |
| timestamps    |               |                                |

### `roles`
| Column  | Type     | Notes                    |
|---------|----------|--------------------------|
| id      | bigint PK|                          |
| name    | string   | e.g. super_admin, admin, developer |
| slug    | string   | unique                   |
| timestamps |      |                          |

### `permissions`
| Column      | Type     | Notes                          |
|-------------|----------|--------------------------------|
| id          | bigint PK|                                |
| name        | string   | e.g. projects.create           |
| guard_name  | string   |                                |
| timestamps  |          |                                |

### `model_has_permissions` / `role_has_permissions` (Spatie)
Standard Spatie permission pivot tables.

### `projects`
| Column            | Type         | Notes                                  |
|-------------------|--------------|----------------------------------------|
| id                | bigint PK    |                                       |
| user_id           | bigint FK    | owner → users.id                       |
| server_id         | bigint nullable FK | → servers.id (Phase 9)          |
| name              | string       | human name                             |
| slug              | string       | unique, URL-safe                       |
| type              | enum/string  | wordpress / laravel / static / node   |
| status            | enum/string  | provisioning / live / stopped / failed / suspended |
| runtime           | string nullable | e.g. php / node / nginx             |
| runtime_version   | string nullable | e.g. 8.3 / 22                        |
| subdomain         | string       | the subdomain label                    |
| domain            | string       | base domain (dev.example.com)          |
| hostname          | string       | full hostname (project.dev.example.com)|
| storage_limit     | int          | MB                                       |
| memory_limit      | string       | e.g. 1024m                               |
| cpu_limit         | int          | millicores / cores                       |
| timestamps        |            |                                        |

Unique constraint on `slug`, and on the full `hostname`.

### `project_domains`
| Column      | Type      | Notes                          |
|-------------|-----------|--------------------------------|
| id          | bigint PK |                                |
| project_id  | bigint FK |                                |
| hostname    | string    |                                |
| type        | string    | subdomain / custom             |
| ssl_status  | string    | none / issuing / active        |
| timestamps  |          |                                |

### `project_databases`
| Column          | Type      | Notes                              |
|-----------------|-----------|------------------------------------|
| id              | bigint PK |                                    |
| project_id      | bigint FK |                                    |
| name            | string    |                                    |
| engine          | string    | mariadb / mysql / postgresql       |
| user            | string    | app user (not root)                |
| password        | text      | encrypted at rest                  |
| port            | int       | internal port                      |
| timestamps      |         |                                    |

### `deployments`
| Column        | Type        | Notes                          |
|---------------|-------------|--------------------------------|
| id            | bigint PK   |                                |
| project_id    | bigint FK   |                                |
| number        | int         | sequential per project         |
| status        | string      | pending / success / failed     |
| commit        | string nullable |                            |
| command       | string      | deploy / restart / migrate ... |
| duration_ms   | bigint nullable |                          |
| logs          | text        |                                 |
| timestamps    |             |                                 |

### `environment_variables`
| Column      | Type      | Notes                          |
|-------------|-----------|--------------------------------|
| id          | bigint PK |                                |
| project_id  | bigint FK |                                |
| key         | string    | e.g. APP_ENV                   |
| value       | text      | **encrypted at rest**          |
| is_secret   | bool      |                                |
| timestamps  |           |                                |

### `audit_logs`
| Column      | Type      | Notes                          |
|-------------|-----------|--------------------------------|
| id          | bigint PK |                                |
| user_id     | bigint nullable FK | → users.id             |
| action      | string    | e.g. project.created           |
| description | text      |                                |
| context     | jsonb nullable | changes / ip / user-agent  |
| timestamps  |           |                                |

### `servers` (Phase 9, created in Phase 1 schema)
| Column        | Type        | Notes                          |
|---------------|-------------|--------------------------------|
| id            | bigint PK   |                                |
| name          | string      |                                |
| ip            | string      |                                |
| agent_url     | string      |                                |
| agent_token   | text        | encrypted                      |
| status        | string      | online / offline               |
| timestamps    |             |                                |

## Full platform table list (later phases)

`users`, `roles`, `permissions`, `model_has_permissions`, `role_has_permissions`,
`projects`, `project_domains`, `project_containers`, `project_databases`,
`deployments`, `deployment_logs`, `environment_variables`, `backups`, `servers`,
`server_agents`, `ssl_certificates`, `audit_logs`, `notifications`.
