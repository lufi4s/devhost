# API specification

RESTful API served by Laravel. All routes except `/api/auth/login` and
`/api/auth/register` require a Sanctum token:

```
Authorization: Bearer <token>
```

Base URL (dev): `http://localhost:8000`

Content type: `application/json`. Errors return `422` (validation), `401` (unauth),
`403` (forbidden / policy), `404` (not found).

## Auth

### POST /api/auth/login
```json
{ "email": "dev@example.com", "password": "secret" }
```
Returns `{ token, user }`.

### POST /api/auth/register
```json
{ "name": "Sai", "email": "sai@example.com", "password": "secret", "password_confirmation": "secret" }
```
Returns `{ token, user }`. (Admin-only in production; open in local dev.)

### POST /api/auth/logout
No body. Invalidates the token.

### GET /api/auth/me
Returns the authenticated user.

## Projects

### GET /api/projects
List projects (paginated). Query: `?page=&per_page=&status=&type=`

### POST /api/projects
```json
{
  "name": "api-service",
  "subdomain": "api-service",
  "domain": "dev.example.com",
  "type": "node",
  "php_version": null,
  "node_version": "22",
  "database": null,
  "git_repository": null,
  "git_branch": "main",
  "storage_limit": 20480,
  "memory_limit": "2048m",
  "cpu_limit": 2
}
```
Validates name/subdomain/hostname uniqueness, dispatches `CreateProjectJob`, returns
the project with `status = provisioning`.

### GET /api/projects/{project}
Full project detail with related domains, database, env vars (masked), latest
deployment.

### PATCH /api/projects/{project}
Update name, resource limits, env vars.

### DELETE /api/projects/{project}
Dispatches `DeleteProjectJob`. Sets status to `deleting`.

### POST /api/projects/{project}/deploy
Dispatches `DeployProjectJob`. Body: `{ "commit": null }` (null = latest).

### POST /api/projects/{project}/restart
Dispatches a restart job.

### POST /api/projects/{project}/stop
Stops the project's containers.

### POST /api/projects/{project}/start
Starts the project's containers.

### GET /api/projects/{project}/deployments
Paginated deployment history.

### GET /api/projects/{project}/logs
Streams application logs. Secrets are masked (`PASSWORD`, `TOKEN`, `API_KEY`, `SECRET`).

### GET /api/projects/{project}/metrics
CPU / RAM / disk / container status / HTTP status / response time.

### Domains
- `POST /api/projects/{project}/domains`
- `DELETE /api/projects/{project}/domains/{domain}`

### Databases
- `GET /api/projects/{project}/databases`
- `POST /api/projects/{project}/databases`

## Environment variables

### GET /api/projects/{project}/env
Returns `{ key, is_secret }` for each var. Secret **values are never returned**.

### POST /api/projects/{project}/env
```json
{ "key": "APP_ENV", "value": "production", "is_secret": false }
```
Secret values are encrypted at rest.

### DELETE /api/projects/{project}/env/{id}

## Audit logs

### GET /api/audit-logs
Paginated. Admin-only.

## RBAC

Every route is guarded by a Laravel Policy:

| Action            | super_admin | admin | developer |
|-------------------|:-----------:|:-----:|:---------:|
| projects.create   |     ✅      |   ✅  |    ✅     |
| projects.edit     |     ✅      |   ✅  |    ✅     |
| projects.delete   |     ✅      |   ✅  |    ❌     |
| users.*           |     ✅      |   ❌  |    ❌     |
| audit.logs        |     ✅      |   ✅  |    ❌     |

Developer projects are scoped: a developer only sees/edits projects they own.
