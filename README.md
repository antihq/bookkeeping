# anithq/bookkeeping

A modern Laravel-based personal finance and bookkeeping application built with Livewire and Flux UI.

[Live Demo](https://bookkeeping.antihq.com)

## Features

- **Dashboard** - Team overview with total balance, monthly income/expenses, and month-over-month comparisons
- **Accounts** - Manage Checking, Savings, Credit Card, Cash, and Other account types
- **Transactions** - Track income and expenses with date, payee, category, notes, and account linkage
- **Categories** - Team-specific categories with on-the-fly creation during transaction entry
- **Team Collaboration** - Create teams, invite members via email, assign admin/member roles
- **User Management** - Profile editing, email verification, password reset, two-factor authentication
- **Device Management** - View and manage active sessions
- **Theme Support** - Light/Dark/System theme preference

## Tech Stack

- **Backend**: PHP 8.4, Laravel 12
- **Frontend**: Livewire 4, Flux UI Pro, Tailwind CSS 4
- **Authentication**: Laravel Fortify, Jetstream, Sanctum
- **Testing**: Pest 4
- **Database**: SQLite (default), supports MySQL/PostgreSQL

## Installation

### Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js and npm

### Quick Setup

```bash
git clone https://github.com/antihq/antihq-bookkeeping.git
cd antihq-bookkeeping
composer run setup
```

The setup script will:
- Install PHP dependencies
- Copy `.env.example` to `.env`
- Generate application key
- Run database migrations
- Install npm dependencies
- Build frontend assets

## Development

### Start Development Server

```bash
composer run dev
```

This will start the Laravel server, queue worker, log watcher, and Vite dev server concurrently.

### Testing

Run the full test suite (includes linting):

```bash
composer run test
```

Run tests only:

```bash
php artisan test --compact
```

### Code Style

Format code with Laravel Pint:

```bash
vendor/bin/pint --dirty
```

## Usage

### Creating Accounts

1. Navigate to Accounts page
2. Click "Add account"
3. Select account type, name, currency, and starting balance
4. Save the account

### Adding Transactions

1. Click "Add transaction" on the dashboard or transactions page
2. Enter transaction details (date, payee, amount, category, note)
3. Select expense or income type
4. Save the transaction

### Managing Teams

1. Go to team settings
2. Invite members by email
3. Assign appropriate roles (admin/member)
4. Team members will receive an email invitation

## Project Structure

- `app/Models/` - Eloquent models (Account, Transaction, Category, Team, User)
- `app/Policies/` - Authorization policies for all resources
- `resources/views/pages/` - Livewire page components
- `resources/views/components/` - Reusable Blade components
- `database/migrations/` - Database migrations
- `tests/Feature/` - Feature tests
- `tests/Unit/` - Unit tests

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Run tests and linting:

```bash
composer run test
vendor/bin/pint --dirty
```

5. Submit a pull request

## License

This project is licensed under the [O'Saasy License](https://osaasy.dev).

**What this means:**

- You're free to use, modify, and distribute this software
- You can include it in commercial projects
- You can sell products that use this software

**Restriction:**

- You may not offer this software (or modified versions) as a SaaS/hosted service that directly competes with the original licensor.

Basically, it's a "do whatever you want" license like MIT, but commercial SaaS rights are reserved for the copyright holder.
