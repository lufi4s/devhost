# Architecture

Complete architecture for the Developer Hosting Platform. This document covers the
whole roadmap; Phase 1 implements the control plane and the frontend. The Node Agent
(Phase 9) is described here but built in a later phase.

## 1. High-level architecture

```
                          Internet
                             │
                             ▼
                        Cloudflare
                             │        (DNS + automatic HTTPS / Origin CA)
                             ▼
                    ┌──────────────────┐
                    │ Reverse Proxy    │
                    │ Nginx (host)     │
                    └───────┬──────────┘
                            │
        ┌───────────────────┼───────────────────┐
        ▼                   ▼                   ▼
  WordPress container   Laravel container   Node.js container
        │                   │                   │
        └───────────────────┼───────────────────┘
                            │
                            ▼
                     Database (per project)
```

- Every project runs inside its own **Docker compose project** (network + containers),
  giving filesystem and network isolation.
- A single host Nginx terminates traffic and reverse-proxies to the correct container
  based on the `Host` header (`project.dev.example.com` → `project-nginx:80` or
  `project-node:3000`).
- DNS and SSL are managed centrally (Cloudflare + Origin CA, or Let's Encrypt).

## 2. Control plane (Phase 1)

```
Frontend (Next.js)
      │  REST + Sanctum token
      ▼
Laravel API (backend/)
      │  dispatches jobs
      ▼
Redis queue ──► Worker (Laravel queue:work)
      │
      │  (Phase 1: in-process provisioning stub)
      │  (Phase 9: HTTPS + signed token / mTLS)
      ▼
Node Agent (agent/) ──► Docker on the node
```

### Components

| Component   | Repo        | Responsibility                                             |
|-------------|-------------|------------------------------------------------------------|
| Frontend    | `frontend/` | Auth UI, project CRUD, deployments, logs, settings         |
| Backend     | `backend/`  | Auth, RBAC, projects, domains, databases, deployments, API |
| Queue worker| `backend/`  | Runs provisioning/deploy jobs off Redis                    |
| Node Agent  | `agent/`    | Talks to Docker/Nginx/filesystem on a node (Phase 9)       |

## 3. Provisioning flow

A developer clicks **Create Project**. The API only validates and **dispatches a job**;
it never provisions synchronously.

```
CreateProjectJob
   ├─ ValidateProjectJob      (name, subdomain, uniqueness)
   ├─ CreateNetworkJob        (Docker network for the project)
   ├─ CreateDatabaseJob       (if the type needs one)
   ├─ CreateApplicationJob    (WordPress / Laravel / Static / Node)
   ├─ ConfigureProxyJob       (Nginx route for the subdomain)
   ├─ ConfigureDNSJob         (Cloudflare A/CNAME record)
   ├─ IssueSSLJob             (Origin CA / Let's Encrypt)
   ├─ HealthCheckJob          (HTTP/TCP probe)
   └─ ProjectReady            (status = LIVE)
```

Every step is **retryable, idempotent, logged, and timeout-protected**. If any step
fails, the project status becomes `PROVISIONING_FAILED` and the dashboard shows the
failing step and reason with a **Retry** action.

## 4. Isolation & security model

- **Container isolation** — each project is its own Docker network + containers. No
  shared volumes between projects.
- **No Docker socket to developers** — developer containers never mount
  `/var/run/docker.sock`. Only the Node Agent (trusted, mTLS) can talk to Docker.
- **No privileged containers** — `privileged: false`, non-root users where possible.
- **No arbitrary shell** — the API exposes only **predefined actions** (Install
  Dependencies, Build, Restart, Clear Cache, Run Migration, Deploy). There is no
  `POST /execute-command`.
- **RBAC** — Super Admin / Admin / Developer with granular permissions enforced by
  Laravel Policies.
- **Secrets** — environment variables and DB passwords are encrypted at rest
  (Laravel `encrypter`) and never logged or returned by the API.
- **Input validation** — project name, subdomain, repo URL, branch, env vars, and
  commands are all validated against strict allowlists.

## 5. Multi-node (Phase 9)

```
Control Plane (backend + worker)
      │  HTTPS + signed token / mTLS
      ├── Node Agent (Node 01) ──► Docker, Nginx, files, monitoring
      ├── Node Agent (Node 02)
      └── Node Agent (Node 03)
```

Each compute node runs a lightweight Node Agent. The control plane assigns projects to
servers and the agent performs the actual Docker operations.

## 6. Phase 1 scope

Phase 1 implements the control plane and frontend so a developer can:

1. Log in (email/password, Sanctum tokens).
2. Create a project (name, subdomain, type, optional PHP/Node version, DB).
3. See the project in a list and on a details page.
4. See provisioning status progress to `LIVE` (provisioning is a real queued job;
   the Docker side is a stub that records what it *would* do).
5. View deployments, logs, environment variables, and settings.

The Docker/Nginx/DNS/SSL operations are wired to a provisioning service with a stub
executor in Phase 1 and a real Node Agent executor in Phase 9.
