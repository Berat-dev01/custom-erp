# ERP Engine

A production-ready, white-label Laravel ERP built for rapid client delivery. Covers HR, payroll, finance, procurement, inventory, sales, projects, and fixed assets in a single coherent system.

Designed for single-tenant deployments by default — each client gets their own installation, database, and domain.

---

## Features

| Area | Capabilities |
|---|---|
| **HR & Employees** | Employee records, departments, positions, documents, org chart |
| **Attendance** | Daily check-in/out, overtime, monthly reports |
| **Leave Management** | Leave types, balance tracking, approval workflow |
| **Payroll** | Salary structures, Turkish SGK/tax calculation, payslips, PDF |
| **Finance** | Chart of accounts, journal entries, bank accounts, checks |
| **Invoicing** | Sale & purchase invoices, VAT, e-Fatura fields, PDF |
| **Expenses** | Expense categories, payment methods, monthly tracking |
| **Procurement** | Purchase orders, receiving, supplier management |
| **Sales** | Sales orders, delivery tracking, customer management |
| **Inventory** | Multi-warehouse stock, stock movements, BOM, work orders |
| **Projects** | Project tracking, task board, time entries |
| **Fixed Assets** | Asset register, depreciation schedule, category management |
| **Reports** | P&L, balance sheet, cash flow, payroll summaries |
| **API** | Token-protected `/api/erp` layer |
| **Security** | Role-based permissions, audit trail, rate limiting |

---

## Quick Start (Docker)

Development runs entirely inside Docker. No local PHP, Composer, or Node required.

```bash
cp .env.example .env
make up
make composer CMD="install"
make artisan CMD="key:generate"
make fresh
```

App: **http://localhost:8082**

### Demo Account

| Email | Password |
|---|---|
| `admin@erp.test` | `password` |

### Seed Data

```bash
# Full demo dataset — 50 employees, 500 products, invoices, payroll, attendance, projects…
make fresh
```

---

## Admin Panel Integration

ERP views are built on the embedded `admin-panel` layout and admin guard. The package source lives under `app/AdminPanel/` — no external package dependency at runtime.

ERP screens run under `/admin/erp`. Admin authentication is handled by the `admin` guard configured in `config/admin-panel.php`.

---

## Demo Reset

Set `APP_ENV=demo` in `.env` to unlock the `demo:reset` command. The command runs `migrate:fresh --seed --force` and refuses to execute in any other environment.

```bash
# Manual reset
php artisan demo:reset

# Or via Docker
make artisan CMD="demo:reset"
```

To schedule an automatic nightly reset, configure the scheduler in `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('demo:reset')->dailyAt('02:00');
```

Then register the scheduler cron on the server (once):

```cron
* * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1
```

---

## Testing & QA

```bash
make test
make fresh
make composer CMD="validate --strict"
```

---

## Production

Production does not use Docker. Target stack: **Nginx · PHP-FPM · MySQL · Redis · Supervisor (queue) · Cron (scheduler) · SSL/TLS**.

---

## Project Structure

```
app/Erp/               Business logic, models, controllers, policies
app/AdminPanel/        Embedded admin panel (layout, middleware, facade)
config/admin-panel.php Admin panel configuration
config/erp.php         ERP configuration
routes/erp.php         ERP route definitions
resources/views/erp/   Blade views
docs/                  Internal development documentation (git-ignored)
```

---

## License

Proprietary. All rights reserved. Not for public distribution.
