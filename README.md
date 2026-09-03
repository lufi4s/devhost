# Developer Hosting Platform

An internal developer hosting platform that lets a developer **create a project,
choose an application type, enter a subdomain, and have everything provisioned
automatically** — containers, Nginx routing, DNS, SSL, and databases.

> This is **not** a cPanel replacement. The developer should never touch Nginx,
> PHP-FPM, Docker, ports, databases, or SSL by hand.

## Stack

| Layer        | Technology                                                        |
|--------------|-------------------------------------------------------------------|
| Frontend     | Next.js (App Router) + TypeScript + Tailwind CSS                  |
| Backend      | Laravel (PHP 8.3+) + Sanctum + PostgreSQL                         |
| Provisioning | Laravel Queues (Redis) → Node Agent → Docker                      |
| Reverse proxy | Nginx                                                            |
| Infra        | Docker + Docker Compose                                           |

## Repository layout (monorepo)

```
developer-hosting-platform/
├── frontend/          # Next.js App Router UI
├── backend/           # Laravel REST API + queue workers
├── agent/             # Node agent that talks to Docker on a node
├── infrastructure/
│   ├── docker/
│   ├── nginx/
│   ├── templates/     # wordpress / laravel / node / static
│   └── scripts/
├── docs/              # ARCHITECTURE, API, SECURITY, INSTALLATION, ...
├── docker-compose.yml # Phase 1 dev environment
├── .env.example
└── README.md
```

## Project types (roadmap)

| Phase | Type        | Status |
|-------|-------------|--------|
| 1     | Foundation  | ✅ this repo |
| 2     | Static HTML | next   |
| 3     | WordPress   |      |
| 4     | Laravel     |      |
| 5     | Node.js     |      |
| 6     | Git deploy  |      |
| 7     | Backups     |      |
| 8     | Monitoring  |      |
| 9     | Multi-node  |      |

## Quick start (Phase 1)

```bash
cp .env.example .env
docker compose up --build
```

- Frontend: http://localhost:3000
- API: http://localhost:8000
- First user is created via `php artisan devhost:user` (see `docs/INSTALLATION.md`)

See [`docs/INSTALLATION.md`](docs/INSTALLATION.md) for the full walkthrough.

## Security model

- Developers **never** get host root, the Docker socket, or arbitrary shell.
- Every mutating API endpoint is protected by Sanctum tokens + RBAC policies.
- Provisioning happens on a **queue**, never inside an HTTP request.
- The Node Agent authenticates with signed tokens / mTLS and is isolated from the public internet.
- Secrets are encrypted at rest and never logged or displayed.

See [`docs/SECURITY.md`](docs/SECURITY.md) for the full threat model.
