# Zetu POS

A multi-purpose Point of Sale system built on Laravel. Handles sales, invoicing,
quotations, purchase orders, inventory, customers, suppliers, accounts, reports,
and employee management with role-based access.

## Stack

- Laravel 5.8 / PHP 7.4
- MySQL
- Blade + jQuery/Vue front end (Laravel Mix)

## Deploying to Railway

This repo includes a `Dockerfile` pinned to PHP 7.4 (required — Laravel 5.8 is
not compatible with newer PHP) and a `railway.json` that tells Railway to build
from it directly.

1. **Create a new Railway project** from this GitHub repo
   (`learninghub44/smart-pos-laravel`).
2. **Add a MySQL database** to the project (Railway → New → Database → MySQL).
   Railway will expose `MYSQLHOST`, `MYSQLPORT`, `MYSQLDATABASE`,
   `MYSQLUSER`, `MYSQLPASSWORD` — map these to the app's env vars:
   - `DB_HOST` = `${{MySQL.MYSQLHOST}}`
   - `DB_PORT` = `${{MySQL.MYSQLPORT}}`
   - `DB_DATABASE` = `${{MySQL.MYSQLDATABASE}}`
   - `DB_USERNAME` = `${{MySQL.MYSQLUSER}}`
   - `DB_PASSWORD` = `${{MySQL.MYSQLPASSWORD}}`
3. **Set the remaining env vars** on the app service (see `.env.example`):
   - `APP_NAME=Zetu POS`
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_KEY` — leave unset for the first deploy; the container will
     generate one on boot and log it. Copy that value back into the
     `APP_KEY` Railway variable so it's stable across future deploys/restarts.
   - `APP_URL` = your Railway-generated domain (or custom domain once attached)
4. **Deploy.** On boot the container automatically runs migrations
   (`RUN_MIGRATIONS=false` to disable), links `storage`, and caches
   config/routes/views.
5. **Create your admin user** — the default seeded admin has been removed on
   purpose. Once deployed, use Railway's shell/exec on the service:
   ```
   php artisan tinker
   >>> \App\User::create(['name'=>'Your Name','email'=>'you@yourdomain.com','password'=>bcrypt('a-strong-password'),'role'=>'admin']);
   ```
6. **Set your business info** — company name, address, currency, VAT, and
   logo are stored in the `settings` table and editable from the app's
   Settings page once logged in (defaults come from `SettingTableSeeder`).

## Local development

```
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
npm install && npm run dev
php artisan serve
```

## Project structure

- Models: `app/`
- Controllers: `app/Http/Controllers`
- Views: `resources/views`
- Migrations: `database/migrations`
- Seeders: `database/seeds`
