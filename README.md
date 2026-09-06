# Helpdesk — IT Ticketing Application

A small, production-ready helpdesk / IT ticketing system for L1 → L2 support
workflows. Built with **Laravel 12**, **PostgreSQL**, server-rendered **Blade**
UI (Tailwind + Alpine + Chart.js), and a token-authenticated **REST API**.

The design goal is *boring and maintainable*: one framework, one language, a
flat controller/service/model structure, and Docker Compose for deployment.

---

## Contents

1. [Features](#features)
2. [Architecture](#architecture)
3. [Project structure](#project-structure)
4. [Roles & permissions](#roles--permissions)
5. [Data model](#data-model)
6. [Local development](#local-development)
7. [Configuration](#configuration)
8. [Deployment (Docker Compose on a VPS)](#deployment-docker-compose-on-a-vps)
9. [Reverse proxy & HTTPS](#reverse-proxy--https)
10. [Backups](#backups)
11. [REST API](#rest-api)
12. [Tests](#tests)
13. [Maintenance notes](#maintenance-notes)

---

## Features

| Area | What it does |
|------|--------------|
| Auth | Session login/logout, login throttling, disabled-account enforcement |
| RBAC | 4 roles (Admin, L1, L2, Viewer), enforced by policies + query scopes on the **server** |
| Tickets | Create, view, edit, assign, comment (public + internal notes), change status, attachments |
| Escalation | One-click L1 → L2 escalation with optional note and L2 assignee; full audit trail |
| Activity log | Append-only history for every meaningful action on a ticket |
| Dashboard | Status/priority/assignment counts, "waiting for L2", 3 charts, recent & my-open lists |
| Search & filter | Free text, reference, status, priority, category, assignee, team/tier, date range |
| Export | Ticket list → CSV / Excel (XLSX) / PDF; single ticket → PDF (with history) |
| Reports | Date-range report: totals, status, priority, category, L1 vs L2, per assignee, avg/median resolution time; export to CSV / XLSX / PDF |
| User management | Admin CRUD, enable/disable, role assignment, password reset |
| Category management | Admin: create, rename, hide/show, delete (with ticket counts) |
| API | REST endpoints mirroring the above, Sanctum bearer tokens, same RBAC |

---

## Architecture

```
Browser ─┬─ Blade UI  (session auth, CSRF)      ┐
         │                                       ├─ Controllers ─ Services ─ Eloquent ─ PostgreSQL
API client ─ REST /api  (Sanctum bearer token)  ┘                    │
                                                        ActivityLogger writes ticket_activities
                                                        Local disk  stores attachments
```

* **Controllers** are thin. They authorize, validate (Form Requests), and
  delegate to a **Service**.
* **Services** (`app/Services`) hold all write logic and are shared by the web
  and API controllers so business rules exist in exactly one place:
  * `TicketService` — create/update/status/assign/escalate/comment
  * `AttachmentService` — file storage
  * `ActivityLogger` — the audit trail
  * `DashboardService` / `ReportService` — read-side aggregation
* **Authorization**: `TicketPolicy` / `UserPolicy` / `CategoryPolicy` for
  single-record actions; role middleware (`role:admin`) guards whole admin
  sections. Ticket *reads* are open to all authenticated roles;
  `Ticket::scopeVisibleTo()` is kept as the single hook to reintroduce
  row-level filtering if that ever needs to change.
* **Enums** (`app/Enums`) define statuses, priorities, tiers and role names.

---

## Project structure

```
app/
  Enums/            RoleName, TicketStatus, TicketPriority, SupportTier
  Exports/          SpreadsheetExporter, TicketExporter, ReportExporter
  Http/
    Controllers/
      Auth/         login/logout
      Web/          Blade UI controllers
      Api/          JSON API controllers
    Middleware/     RoleMiddleware, EnsureUserIsActive
    Requests/       Form Request validation (grouped by feature)
    Resources/      API JSON resources
  Models/           User, Role, Category, Ticket, TicketComment, TicketActivity, Attachment
  Policies/         TicketPolicy, UserPolicy
  Services/         business logic (see above)
  Support/          TicketFilters (query-string → filter array)
config/             standard Laravel config + helpdesk.php (app-specific settings)
database/
  migrations/       schema
  seeders/          RoleSeeder, CategorySeeder, UserSeeder, DemoTicketSeeder
  factories/        test factories
resources/views/    Blade templates (layouts, partials, feature folders, exports/*)
routes/             web.php, api.php, console.php
docker/             php/Dockerfile, php/entrypoint.sh, php/php.ini, nginx/default.conf
tests/              Feature + Unit tests
```

---

## Roles & permissions

| Capability | Admin | L1 | L2 | Viewer |
|---|:--:|:--:|:--:|:--:|
| View dashboard / reports | ✅ | ✅ | ✅ | ✅ |
| **View any ticket** (incl. after escalation) | ✅ | ✅ | ✅ | ✅ |
| Create ticket | ✅ | ✅ | — | — |
| Update / status / assign | ✅ | L1 queue* | L2 queue* | — |
| Escalate L1 → L2 | ✅ | ✅ | — | — |
| Comment (public) | ✅ | ✅ | ✅ | — |
| Internal notes | ✅ | ✅ | ✅ | (hidden) |
| Upload attachments | ✅ | L1 queue* | L2 queue* | — |
| Delete ticket / attachment | ✅ | — | — | — |
| Export & reports | ✅ | ✅ | ✅ | ✅ |
| Category management | ✅ | — | — | — |
| User management | ✅ | — | — | — |

**Reading is not restricted** — every authenticated role can open every ticket,
so L1 keeps sight of a ticket after escalating it. **Writing is queue-gated:**

\* *queue* = the tier the agent owns (L1 → L1 queue, L2 → L2 queue) **plus** any
ticket they created or are assigned to. Closed tickets are read-only for
non-admins. **All deletion is Admin-only.**

Roles are fixed and seeded once (`RoleSeeder`). Categories are managed by admins
under **Categories**. Change a user's role from **Users → Edit**.

---

## Data model

```
roles (1) ───< users (1) ───< tickets >─── (1) categories
                   │                │
       created_by/assigned_to       ├──< ticket_comments      (user_id, body, is_internal)
                                    ├──< ticket_activities    (user_id, event, description, properties json)
                                    └──< attachments          (uploaded_by, disk, path, original_name, size)
```

Ticket lifecycle: `Open → In Progress → Pending → Resolved → Closed`
(reopening is allowed and logged). `support_tier` is `l1` or `l2`.
Resolution timestamps (`resolved_at`, `closed_at`, `first_responded_at`,
`escalated_at`) are maintained automatically by `TicketService`.

Migrations include indexes on the columns used for filtering/reporting
(`status`, `priority`, `support_tier`, `assigned_to`, `created_by`,
`created_at`, and a composite `(support_tier, status)`).

---

## Local development

Requirements: PHP 8.3 (`pdo_pgsql`, `gd`, `zip`, `intl`, `bcmath`),
Composer 2, Node 20, and PostgreSQL 16 (or use the Docker `db` service).

```bash
cp .env.example .env
# edit .env: set DB_* to your local Postgres, APP_ENV=local, APP_DEBUG=true

composer install
npm install

php artisan key:generate
php artisan migrate --seed          # roles, categories, admin (+ demo users in non-prod)
php artisan db:seed --class=DemoTicketSeeder   # optional sample tickets

npm run dev                         # Vite dev server
php artisan serve                   # http://127.0.0.1:8000
```

> Commit the generated `composer.lock` and `package-lock.json` so Docker builds
> are reproducible.

Non-production seeding creates demo accounts (password `password`):
`admin@example.com`, `l1@example.com`, `l2@example.com`, `viewer@example.com`
— override the admin via `ADMIN_EMAIL` / `ADMIN_PASSWORD` in `.env`.

---

## Configuration

All configuration is via environment variables (`.env`). Key entries:

| Variable | Purpose |
|---|---|
| `APP_KEY` | Encryption key — **must be set and stable** (`php artisan key:generate --show`) |
| `APP_URL` | Public URL, used for links and cookie scope |
| `DB_*` | PostgreSQL connection |
| `SESSION_DRIVER` | `database` (default) — no extra service needed |
| `SESSION_SECURE_COOKIE` | `true` in production (HTTPS) |
| `ATTACHMENT_MAX_SIZE_KB` | Per-file upload limit (default 10 MB) |
| `ATTACHMENT_ALLOWED_EXTENSIONS` | Comma list of accepted file types |
| `SANCTUM_TOKEN_EXPIRATION` | Minutes; blank = non-expiring API tokens |
| `CORS_ALLOWED_ORIGINS` | Comma list of browser origins allowed to call `/api/*` |
| `ADMIN_NAME` / `ADMIN_EMAIL` / `ADMIN_PASSWORD` | Initial admin created by the seeder |

App-specific defaults live in `config/helpdesk.php`.

---

## Deployment (Docker Compose on a VPS)

The stack: **`app`** (PHP-FPM + app code + built assets), **`web`** (Nginx),
**`db`** (PostgreSQL 16). Attachments and the DB live in named volumes.

### 1. Install prerequisites on the VPS

```bash
# Docker Engine + Compose plugin (Debian/Ubuntu)
curl -fsSL https://get.docker.com | sh
```

### 2. Get the code and configure

```bash
git clone <your-repo> /opt/helpdesk && cd /opt/helpdesk
cp .env.example .env
```

Edit `.env`:

* `APP_ENV=production`, `APP_DEBUG=false`
* `APP_URL=` — the exact URL you browse to (`http://SERVER_IP:8080` while testing,
  `https://helpdesk.example.com` once a domain + TLS are in place)
* `DB_PASSWORD=` — a strong password
* `ADMIN_EMAIL` / `ADMIN_PASSWORD` — your first admin
* `SESSION_SECURE_COOKIE` — `false` while serving over plain HTTP, `true` once
  you are on HTTPS (leaving it `true` on HTTP makes every login fail with a 419)

Generate the app key and paste it into `.env`:

```bash
docker compose run --rm app php artisan key:generate --show
# -> APP_KEY=base64:....   (copy this line into .env)
```

### 3. Build and start

```bash
docker compose build
docker compose up -d
```

On first start the `app` container waits for Postgres, runs `migrate --force`,
runs the seeders (roles, categories, admin), and caches config/routes/views.
Watch it with `docker compose logs -f app`.

The app is now on `http://<vps-ip>:8080` (change `APP_PORT` in `.env`).

### 4. Updating to a new version

```bash
cd /opt/helpdesk
git pull
docker compose build
docker compose up -d          # entrypoint re-runs migrations automatically
```

### Build instructions summary

* Front-end assets are built **inside** the Docker image (Vite, stage 1) — no
  Node needed on the VPS.
* PHP dependencies are installed with `--no-dev` (Composer, stage 2).
* The runtime image is `php:8.3-fpm-alpine` with `pdo_pgsql`, `gd`, `zip`,
  `intl`, `bcmath`, `opcache`.

---

## Reverse proxy & HTTPS

The `web` container serves plain HTTP on the port you mapped (`APP_PORT`).
Terminate TLS with a reverse proxy on the host. Two common options:

### Option A — Caddy (automatic Let's Encrypt)

`/etc/caddy/Caddyfile`:

```
helpdesk.example.com {
    reverse_proxy 127.0.0.1:8080
}
```

```bash
sudo apt install caddy && sudo systemctl reload caddy
```

### Option B — Nginx + Certbot on the host

```nginx
server {
    listen 80;
    server_name helpdesk.example.com;
    location / { return 301 https://$host$request_uri; }
}

server {
    listen 443 ssl http2;
    server_name helpdesk.example.com;

    ssl_certificate     /etc/letsencrypt/live/helpdesk.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/helpdesk.example.com/privkey.pem;

    client_max_body_size 25M;

    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_set_header Host              $host;
        proxy_set_header X-Real-IP         $remote_addr;
        proxy_set_header X-Forwarded-For   $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

```bash
sudo certbot --nginx -d helpdesk.example.com
```

Because Laravel sits behind a proxy, `APP_URL` must be the `https://` URL and
`SESSION_SECURE_COOKIE=true`. The app trusts `X-Forwarded-*` headers from any
proxy by default (`TrustProxies` middleware); restrict it in
`bootstrap/app.php` if your proxy IP is fixed.

---

## Backups

**Database** — nightly `pg_dump` via cron on the host:

```bash
# /etc/cron.d/helpdesk-backup
0 2 * * * root docker compose -f /opt/helpdesk/docker-compose.yml exec -T db \
  pg_dump -U helpdesk helpdesk | gzip > /opt/helpdesk-backups/db-$(date +\%F).sql.gz
```

Keep 14–30 days and copy off-site (`rclone`, `restic`, S3, …).

**Attachments** — back up the `attachments` Docker volume:

```bash
0 3 * * * root tar czf /opt/helpdesk-backups/attachments-$(date +\%F).tgz \
  -C /var/lib/docker/volumes/helpdesk_attachments/_data .
```

**Restore**: `gunzip -c db-YYYY-MM-DD.sql.gz | docker compose exec -T db psql -U helpdesk helpdesk`
and extract the attachments tarball back into the volume.

---

## REST API

Base URL: `https://helpdesk.example.com/api`. Auth: bearer token from
`POST /api/auth/login`.

```bash
# 1. Get a token
curl -sX POST https://helpdesk.example.com/api/auth/login \
  -H 'Accept: application/json' \
  -d 'email=admin@example.com&password=secret&device_name=cli'
# -> {"token":"1|abc...","user":{...}}

# 2. Use it
curl -s https://helpdesk.example.com/api/tickets \
  -H 'Accept: application/json' -H 'Authorization: Bearer 1|abc...'
```

| Method & path | Description | Min role |
|---|---|---|
| `POST /api/auth/login` | Exchange credentials for a token | — |
| `GET /api/auth/me` | Current user | any |
| `POST /api/auth/logout` | Revoke the current token | any |
| `GET /api/tickets` | List (supports the same filters as the UI: `q`, `status[]`, `priority[]`, `category_id[]`, `support_tier`, `assigned_to`, `date_from`, `date_to`, `per_page`) | any |
| `POST /api/tickets` | Create | Admin, L1 |
| `GET /api/tickets/{id}` | Show (with comments, activities, attachments) | per visibility |
| `PUT /api/tickets/{id}` | Update fields / status / assignee | queue owner |
| `DELETE /api/tickets/{id}` | Delete | Admin |
| `POST /api/tickets/{id}/comments` | Add comment (`body`, `is_internal`) | agents |
| `POST /api/tickets/{id}/escalate` | Escalate to L2 (`note`, `assigned_to`) | Admin, L1 |
| `POST /api/tickets/{id}/assign` | Assign / unassign (`assigned_to`) | queue owner |
| `POST /api/tickets/{id}/status` | Change status (`status`, `resolution`) | queue owner |
| `GET /api/reports` | Aggregates for `from`/`to` range | any |
| `GET /api/categories`, `GET /api/meta`, `GET /api/dashboard` | Lookups & dashboard data | any |
| `GET /api/users`, `POST /api/users`, `GET/PUT /api/users/{id}` | User management | Admin |

Errors are JSON (`422` validation, `401` unauthenticated, `403` forbidden).
API rate limit: 60 req/min per token; login: 10 req/min per IP.

---

## Tests

```bash
php artisan test
```

Feature tests cover auth, ticket RBAC/visibility, the escalation workflow and
the API. They run on an in-memory SQLite database (`phpunit.xml`), so a PHP
build with `pdo_sqlite` is required locally. Code style: `./vendor/bin/pint`.

---

## Maintenance notes

* **Add a ticket category**: **Categories** in the sidebar (admin only). The
  `CategorySeeder` still provides the initial set on a fresh database.
* **Change what counts as an "open" state**: `App\Enums\TicketStatus::openStates()`.
* **Change visibility rules**: `TicketPolicy::view()` currently returns `true`
  for everyone; tighten it there and add the matching `where` in
  `Ticket::scopeVisibleTo()` (used by every list/report/export query).
* **Ticket reference format**: `Ticket::booted()` + `TICKET_REFERENCE_PREFIX`.
* **Queue**: `QUEUE_CONNECTION=sync` by default (no worker needed). Nothing in
  the app currently queues work; switch to `database` and run
  `php artisan queue:work` if you add notifications.
* **Scheduler**: only prunes expired API tokens. If you want it, add
  `* * * * * cd /opt/helpdesk && docker compose exec -T app php artisan schedule:run`.
* **Logs**: `docker compose logs -f app` and `storage/logs/` (mounted volume).
```
