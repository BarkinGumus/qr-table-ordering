# QR Table Ordering

QR-code table ordering for restaurants, cafes and bars. A guest scans the code
on their table, orders from their phone, and the order appears on the kitchen,
bar and waiter screens.

One deployment serves many restaurants. Built with Laravel 13 and Filament 5
on PHP 8.3+ and MariaDB/MySQL.

> **Status: step 1 of 13 (foundation).** Login, roles and restaurant isolation
> work. Tables, QR codes, the menu and ordering arrive in the next steps.

## What works today

- One login page, `/login`, for every role. Throttled per email + IP (5
  attempts a minute) and per IP (20 a minute), with CSRF protection and a
  fresh session ID at login.
- Six roles: platform admin, owner, manager, waiter, kitchen, bar.
- Admin panel at `/admin` for owners, managers and the platform admin. It is
  empty until the next steps add tables, menu and staff screens.
- Staff landing page at `/staff` (a placeholder for the order boards).
- Restaurant isolation: every restaurant-owned model is filtered to the
  current restaurant, and fails loudly if no restaurant is set.
- Turkish and English interface, following the browser language, with a
  switch in the header.

## Run it locally

Requirements: PHP 8.3 or newer (extensions: pdo_mysql, intl, mbstring, zip,
gd), Composer 2, and MariaDB 10.6+ or MySQL 8.

```sh
git clone https://github.com/BarkinGumus/qr-table-ordering.git
cd qr-table-ordering
composer install
cp .env.example .env
php artisan key:generate
```

Create a database and a user for it, then put the password in `.env`
(`DB_PASSWORD`):

```sql
CREATE DATABASE qr_ordering CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'qr'@'localhost' IDENTIFIED BY 'choose-a-password';
GRANT ALL ON qr_ordering.* TO 'qr'@'localhost';
```

Create the tables, load the demo data and start the server:

```sh
php artisan migrate --seed
php artisan serve
```

Open <http://localhost:8000>.

### Demo accounts

Created by `php artisan migrate --seed` outside production. The password for
every account is `password`.

| Email | Role | Lands on |
| --- | --- | --- |
| `owner@demo.test` | Owner | `/admin` |
| `manager@demo.test` | Manager | `/admin` |
| `waiter@demo.test` | Waiter | `/staff` |
| `kitchen@demo.test` | Kitchen | `/staff` |
| `bar@demo.test` | Bar | `/staff` |
| `admin@platform.test` | Platform admin | `/admin` |

Everyone logs in at `/login`; the admin panel has no login page of its own.

The demo seeder refuses to run when `APP_ENV=production`.

## Tests

```sh
php artisan test
```

Tests run on an in-memory SQLite database and need no setup. GitHub Actions
runs them on PHP 8.3 and 8.4 for every pull request.

## Project layout

| Path | What lives there |
| --- | --- |
| `app/Tenancy/` | The current-restaurant holder, the query scope and the `BelongsToRestaurant` trait. |
| `app/Enums/Role.php` | The six roles and what each may do. |
| `app/Http/Middleware/` | Restaurant binding, role checks, language choice, security headers. |
| `app/Http/Controllers/Auth/LoginController.php` | Staff login and logout. |
| `app/Providers/Filament/AdminPanelProvider.php` | The admin panel. |
| `database/migrations/` | Schema. All schema changes go through migrations. |
| `lang/tr`, `lang/en` | Interface texts. |
| `tests/Feature/` | Login, role access and restaurant isolation tests. |

### The one rule for new models

Every table that belongs to a restaurant has a `restaurant_id` column, and its
model uses the trait:

```php
use App\Tenancy\BelongsToRestaurant;

class DiningTable extends Model
{
    use BelongsToRestaurant;
}
```

That is all that is needed: reads are filtered, new rows are stamped, and a
row cannot be created for or moved to another restaurant. Console commands and
seeders wrap their work in `app(Tenant::class)->run($restaurant, fn () => ...)`.

## Deploying

### First login on a new server

Production has no demo accounts. Create the platform admin once:

```sh
php artisan platform:create-admin you@example.com
```

### Settings to change in `.env` for production

| Key | Value |
| --- | --- |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | Your `https://` address |
| `SESSION_SECURE_COOKIE` | `true` |
| `DB_PASSWORD` | A strong password |

Never commit `.env`.

### Docker (VPS)

```sh
cp .env.example .env    # then edit as above

# Generate the encryption key BEFORE the first start and paste the printed
# value into .env as APP_KEY=... (the container reads it from there).
docker compose build app
docker compose run --rm --no-deps --entrypoint php app artisan key:generate --show

docker compose up -d
docker compose exec app php artisan platform:create-admin you@example.com
```

The container refuses to start while `APP_KEY` is empty. After changing
`.env`, run `docker compose up -d` again so the new values are picked up.

The site listens on port 8080 (`APP_PORT` in `.env` changes it). Put it
behind a reverse proxy that terminates HTTPS, such as Caddy or nginx with
Let's Encrypt.

The Docker files have not been run yet: the environment this step was built
in has no Docker. Treat them as a first draft until the operations step.

### Shared hosting (cPanel)

Needs PHP 8.3+ and either SSH with Composer or a locally built `vendor/`
folder uploaded with the code. Point the domain's document root at `public/`.
A tested guide comes with the operations step.

## Roadmap

1. **Foundation** (this step)
2. Migrations and seed data for tables, menu and orders
3. Tables and QR codes
4. Menu admin
5. Guest flow: scan, menu, cart, order, status
6. Table session rules
7. Kitchen, bar and waiter boards
8. Staff accounts
9. Security pass
10. Tests for the full order flow
11. Operations: deployment, backups, logging
12. Privacy basics
13. Pilot
