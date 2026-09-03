# Security model

Security is a primary requirement. This document maps each threat to its mitigation.

## Core rules

1. **A normal developer can never execute arbitrary commands on the host.**
   The API exposes only predefined actions (Install Dependencies, Build, Restart,
   Clear Cache, Run Migration, Deploy). There is **no** `POST /execute-command`.
2. **A compromised app must not reach the host, Docker daemon, other projects, other
   databases, or the control plane.** Enforced by container isolation, no Docker
   socket mounts, and non-root users.
3. **Secrets are encrypted at rest and never logged or returned.**

## Threat → mitigation

| Threat                    | Mitigation                                                                 |
|---------------------------|----------------------------------------------------------------------------|
| SQL injection             | Eloquent / parameterized queries; DB user with minimal privileges.         |
| XSS                       | Next.js escaping; CSP headers; no `dangerouslySetInnerHTML` on user data.  |
| CSRF                      | Sanctum cookie + CSRF token for web sessions; Bearer tokens for API.       |
| IDOR                      | Policies scope every resource to the authenticated user / role.            |
| Privilege escalation      | Spatie permissions + policies; admin-only routes guarded.                  |
| SSRF                      | Repo URLs and env vars validated against allowlists; no host fetch of user URLs. |
| Command injection         | No user shell input; predefined actions only; agent uses APIs not shells.  |
| Path traversal            | File manager operates only inside the project's container path; validated. |
| Container escape          | No `privileged`; no `/var/run/docker.sock` mount; non-root; read-only FS where possible. |
| Secret exposure           | `encrypter` at rest; values masked in logs & API; no secrets in `git`.     |
| Rate-limit bypass         | Laravel rate limiting on auth + mutating endpoints.                        |
| Weak sessions             | Secure, HttpOnly, SameSite cookies; token rotation; 2FA optional.          |

## Input validation allowlists

- **Project name**: `[a-z0-9-]`, 3–63 chars, no leading/trailing dash.
- **Subdomain**: `[a-z0-9-]`, 1–63 chars.
- **Hostname**: `subdomain` + `.` + validated base domain.
- **Git repo URL**: validated scheme (`git@`, `https://`), host allowlist for later
  GitHub/GitLab/Bitbucket.
- **Git branch**: `[A-Za-z0-9._/-]`, no shell metacharacters.
- **Env var key**: `[A-Z_][A-Z0-9_]*`.
- **Env var value**: length-capped, no newline injection (single-line).
- **Commands**: only the predefined action set; never interpolated into a shell.

## Container isolation

- Each project = its own Docker network + containers.
- No shared volumes between projects.
- Developer containers never see the Docker socket or host filesystem.
- Resource limits enforced by Docker (`--cpus`, `--memory`, `--pids-limit`).
- `privileged: false` always.

## Secrets

- `APP_KEY` encrypts all `encrypter`-protected columns (`environment_variables.value`,
  `project_databases.password`, `servers.agent_token`).
- Logs mask `PASSWORD`, `TOKEN`, `API_KEY`, `SECRET` (case-insensitive).
- API responses never include secret values; UI shows `********`.

## Node Agent auth (Phase 9)

- mTLS preferred; fallback to signed API tokens.
- Agent is isolated from the public internet — only the control plane reaches it.
- Every agent call is authenticated and audited.

## Hardening

- Secure HTTP headers (HSTS, CSP, X-Frame-Options, etc.).
- SSH hardening + Fail2ban/CrowdSec on production nodes.
- Firewall rules restrict container exposure to Nginx only.

## Security testing (pre-production)

SQL injection, XSS, CSRF, IDOR, privilege escalation, SSRF, command injection,
path traversal, container escape, secret exposure, rate-limit bypass. Focus areas:
Docker API, file manager, Git repo URLs, deployment commands, env vars, container exec,
Nginx config generation.
