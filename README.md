# VAPT Platform — Ready Build

A simple authorized-use web VAPT application implementing the four-stage workflow:

`Project → Target + Authorization/Scope → Recon → Security Assessment → Controlled Load Test → Findings → PDF Report`

The build is intentionally a single Laravel application with MySQL and database-backed queue workers. It is not an enterprise SOC platform.

## Verification requirements

Production/runtime verification requires PHP 8.2+ with `curl`, `mbstring`, `dom`, `xml`, `xmlwriter`, and `pdo_mysql`. The bundled PHPUnit feature tests use SQLite for isolation, so test execution also requires `pdo_sqlite`. A queue worker and database are required for Steps 1–3; the development queue should not be used for real assessments.

The automated security scanner intentionally uses bounded, non-destructive/passive validation. It does not claim blind confirmation of SQL injection, command injection, SSRF, path traversal, authentication bypass, or access-control flaws from a pattern alone; those require analyst validation and, where appropriate, explicitly configured authorized test identities.

The scope record's authenticated-testing flag records authorization for that activity, but this build does not store credentials or automatically obtain/steal credentials.


## Requirements

- PHP 8.2+
- PHP extensions: `pdo`, `pdo_mysql`, `openssl`, `mbstring`, `dom`, `xml`, `xmlwriter`, `curl`, `fileinfo`
- MySQL 8+ (or a compatible MySQL/MariaDB deployment)
- Composer 2.x
- A web server such as Nginx or Apache
- A background queue worker for the `vapt` queue

The project includes a `vapt:preflight` command that checks required PHP extensions and the configured database before an assessment is started. Run it after `.env` is configured; it returns a non-zero exit code when prerequisites are missing.

## Install

```bash
composer install --no-interaction --prefer-dist
cp .env.example .env
php artisan key:generate

# Create the MySQL database named in .env, then:
php artisan migrate --seed
php artisan storage:link
php artisan vapt:preflight
```

Set the real `DB_*` and mail settings in `.env`. Never copy secrets from another environment. Keep `APP_DEBUG=false` for deployed environments.

Start the application and worker separately:

```bash
php artisan serve
php artisan queue:work database --queue=vapt --sleep=1 --tries=1 --timeout=1800
```

The worker can also be started with the included `run-worker.sh` helper.

## Four-step workflow

1. **Reconnaissance** — DNS, public/resolved network information, HTTP, redirects, cookies, security headers, TLS certificate/handshake observations, robots/sitemap, and technology fingerprints with confidence/source.
2. **Security assessment** — bounded in-scope crawl with URL deduplication, forms/parameter discovery, passive/safe security checks, evidence, and analyst-reviewable findings.
3. **Controlled load test** — explicit authorization confirmation plus hard platform/scope limits for rate, concurrency, duration, total requests, latency/error circuit breakers, progress metrics, and an emergency stop.
4. **Findings/report** — analyst triage, evidence attachments, validation state, remediation, and an 18-section PDF report.

Step order is server-enforced. Long-running stages use database queue jobs rather than normal HTTP requests. Every target request is checked against the active authorization scope before it is sent.

## Scope enforcement

Each project records:

- target URL
- allowed domains/subdomains
- allowed IP/CIDR ranges (IPv4 and IPv6)
- excluded hosts
- allowed ports
- optional allowed endpoint/path patterns
- authorized start/end window
- request-rate, concurrency, duration, and total-request ceilings
- authenticated-testing permission
- authorization notes

Out-of-scope activity is `BLOCK → LOG → SHOW REASON`. The platform does not include unrestricted Internet scanning, destructive exploitation, credential theft, persistence, lateral movement, or a mode intended to keep attacking until a site fails.

## Security of the platform

Authentication uses hashed passwords, login throttling, session regeneration, CSRF protection, email verification, password reset, encrypted cookies, server-side project ownership checks, private evidence/report storage, security headers, input validation, secret redaction in audit contexts, and audit logs for material actions.

The application deliberately reports observed/resolved infrastructure. It does not attempt to bypass CDNs/WAFs to reveal hidden/origin IPs. Technology fingerprints are stored with confidence rather than presented as absolute truth.

## Verification

Run the static checks first:

```bash
composer validate --strict
find app config database routes -name '*.php' -print0 | xargs -0 -n1 php -l
php artisan route:list
php artisan vapt:preflight
```

Then run the end-to-end check with the application and `vapt` worker running. The verification script creates a local scoped project and exercises all four stages without using an external target.

```bash
php verify_e2e.php
php verify_crud.php
```

These checks are destructive to the temporary records they create, so run them only against a disposable test database.

## Deployment notes

Point the web server document root at Laravel's `public/` directory. Configure the queue worker as a supervised process (for example, systemd or Supervisor) and set conservative `VAPT_MAX_*` ceilings appropriate for the engagement. Do not put real credentials, API keys, session tokens, or production `.env` files into source control or the distributed archive.

## Authorized Use

This project is intended for authorized VAPT and security assessment only. Test only systems and targets for which you have explicit permission.
