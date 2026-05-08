# Fight House API

> Backend of a monthly fee management system for a jiu-jitsu academy.

[![Tests](https://github.com/Kuligowskilucas/fighthouse-api/actions/workflows/tests.yml/badge.svg)](https://github.com/Kuligowskilucas/fighthouse-api/actions/workflows/tests.yml)
![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel)
![PHP](https://img.shields.io/badge/PHP-8.5-777BB4?logo=php)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?logo=postgresql)

REST API built with Laravel 13 and PostgreSQL, using Sanctum for authentication. A non‑profit project developed for Fight House Club, replacing a paper‑based system with digital student registration, payment tracking, automatic monthly fee generation, and a monthly dashboard.

> 🇧🇷 Português · [🇺🇸 English](README.en.md) (coming soon)

---

## About the project

Fight House Club is a jiu-jitsu academy whose owner, Marquete, currently manages the monthly fees of ~30 students in a physical notebook. This project is a free digital alternative focused on three things:

1. **Centralized registration** of students with their respective plans.
2. **Payment tracking** with history and automatic delinquency calculation.
3. **Consolidated monthly overview** (received, to receive, overdue) — replacing the need to sum everything manually each month.

v2 is planned to include automatic WhatsApp messages for overdue students; v1 (this repository) delivers the complete backend with test coverage and CI.

---

## Stack

- **PHP 8.5** + **Laravel 13**
- **PostgreSQL 16**
- **Laravel Sanctum** (bearer token authentication)
- **Docker** via Laravel Sail (development environment)
- **PHPUnit** for feature tests
- **GitHub Actions** for CI

---

## Features

### Authentication
- Login with email and password, returning a Sanctum token with 7‑day expiration.
- Logout from current device or all devices (`logout-all`).
- Self‑service password change (requires current password; invalidates other tokens).

### Registrations
- **Plans** with name, price, and weekly frequency.
- **Students** identified by normalized phone number, linked to a plan, with a due date and an optional custom fee amount.
- **Monthly fees** — one per student per month, with an automatically calculated status (paid, overdue, open).

### Monthly fee operations
- Mark and undo payments (records payment method and date).
- Filters by status, student, and reference month.
- **Automatic generation** of monthly fees:
  - Scheduled Artisan command that runs on the 1st of each month.
  - Manual endpoint to trigger from the frontend.
  - Idempotent — can be run multiple times without creating duplicates.

### Dashboard
- Monthly summary: active students, total received, to receive, overdue.
- Delinquent list grouped by student, with amount owed and days overdue.

### Operations
- Artisan commands to create an admin user (`user:create`) and reset a forgotten password (`user:reset-password`).

---

## Local setup

### Prerequisites
- Docker and Docker Compose
- Git

> No need to have PHP, Composer, or Postgres installed locally — everything runs in containers via Laravel Sail.

### Step by step

```bash
# 1. Clone the repository
git clone https://github.com/Kuligowskilucas/fighthouse-api.git
cd fighthouse-api

# 2. Copy the example .env file
cp .env.example .env

# 3. Install Composer dependencies (runs in a container)
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php85-composer:latest \
    composer install --ignore-platform-reqs

# 4. Start the containers
./vendor/bin/sail up -d

# 5. Generate APP_KEY
./vendor/bin/sail artisan key:generate

# 6. Run migrations and seeders
./vendor/bin/sail artisan migrate --seed
```

The API will be available at `http://localhost`. In development, the seeder creates the user **`marquete@fighthouse.local`** with password **`senha123`**, along with 30 fictional students and varied monthly fees.

### Sail alias (recommended)

To avoid typing `./vendor/bin/sail` every time, add this to your `~/.bashrc` or `~/.zshrc`:

```bash
alias sail='[ -f sail ] && sh sail || sh vendor/bin/sail'
```

From that point on, all commands can be invoked as `sail artisan ...`, `sail test`, and so on.

---

## Useful commands

| Command | Description |
|---|---|
| `sail up -d` | Start containers in the background |
| `sail down` | Stop containers |
| `sail artisan migrate:fresh --seed` | Recreate the database from scratch with seed data |
| `sail artisan test` | Run the entire test suite |
| `sail artisan tinker` | Laravel interactive REPL |
| `sail artisan route:list` | List all routes |
| `sail artisan schedule:list` | List scheduled commands |
| `sail artisan user:create` | Create a new user interactively |
| `sail artisan user:reset-password {email}` | Reset a user's password (manual operation) |
| `sail artisan mensalidades:gerar` | Generate current month's fees manually |

---

## Tests

The suite covers the system’s critical flows (authentication, registrations, payments, invoice generation, and user management) with **41 feature tests**:

```bash
sail artisan test
```

The CI pipeline (GitHub Actions) runs the same test suite on every push and pull request to `main`, in a clean Ubuntu + Postgres environment.

---

## API endpoints (Summary)

All routes (except `POST /api/login`) require a Sanctum token in the `Authorization: Bearer {token}`.

### Authentication
| Method | Route | Description |
|---|---|---|
| POST | `/api/login` | Login (rate-limited to 5 requests/minute) |
| POST | `/api/logout` | Logout from the current device |
| POST | `/api/logout-all` | Logout from all devices |
| GET | `/api/me` | Authenticated user data |
| POST | `/api/me/change-password` | Change own password |

### Resources
| Method | Route | Description |
|---|---|---|
| GET / POST | `/api/users` | List / create users |
| GET / POST / PUT / DELETE | `/api/planos` | Plans CRUD |
| GET / POST / PUT / DELETE | `/api/alunos` | Students CRUD (filters: `search`, `ativo`, `plano_id`) |
| GET / POST / PUT / DELETE | `/api/mensalidades` | Monthly payments CRUD (filters: `status`, `aluno_id`, `mes_referencia`) |

### Specific Actions
| Method | Route | Description |
|---|---|---|
| POST | `/api/mensalidades/{id}/marcar-pagamento` | Register payment |
| POST | `/api/mensalidades/{id}/desfazer-pagamento` | Revert payment |
| POST | `/api/mensalidades/gerar` | Generate monthly payments for the current month (manual) |

### Dashboard
| Method | Route | Description |
|---|---|---|
| GET | `/api/dashboard/resumo` | Monthly summary (accepts `?mes=YYYY-MM-DD`) |
| GET | `/api/dashboard/inadimplentes` | List overdue students |

---

## Project Structure

```
app/
├── Console/Commands/      # Artisan commands (user:create, mensalidades:gerar, etc.)
├── Http/
│   ├── Controllers/Api/   # REST controllers
│   ├── Middleware/        # SecurityHeaders
│   ├── Requests/          # Form Requests (validation)
│   └── Resources/         # API Resources (serialization)
├── Models/                # Eloquent models
└── Services/              # MonthlyPaymentGenerator
database/
├── factories/             # Factories for tests and seeders
├── migrations/            # Database schema
└── seeders/               # PlanoSeeder, DatabaseSeeder
routes/
├── api.php                # API routes
└── console.php            # Scheduled commands
tests/
└── Feature/               # Feature tests (41 total)
```

---

## Technical Decisions

Architecture decisions, business rules, scope choices, and pending validation items are documented in [DECISOES.md](DECISOES.md).

---

## License

No formal open-source license. The code is published for portfolio purposes and for use by Fight House Club.
