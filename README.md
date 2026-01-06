# Antibookkeeping

A modern Laravel-based personal finance and bookkeeping application built with Livewire and Flux UI.

## Features

### Financial Management

- Create and manage multiple account types (Checking, Savings, Credit Card, Cash, Other)
- Track income and expenses with detailed transaction records
- Support for 100+ currencies with proper formatting and symbol display
- Real-time balance calculation based on transactions
- Organize transactions with team-specific categories
- Create categories on-the-fly when adding transactions

### Team Collaboration

- Create and manage teams
- Invite team members via email
- Assign roles (admin, member)
- Leave teams when no longer needed
- Team settings management

### User Management

- Profile management (name, email, profile photo)
- Email verification
- Password reset functionality
- Two-factor authentication support
- Light/Dark/System theme preference

### Security

- Role-based access control
- Team-level security (users can only access their team's data)
- Granular permission policies for all resources

## Tech Stack

- **Backend**: Laravel 12, PHP 8.4
- **Frontend**: Livewire 4, Flux UI Pro, Tailwind CSS 4
- **Authentication**: Laravel Fortify, Sanctum, Jetstream
- **Testing**: Pest 4
- **Database**: SQLite (default), MySQL, PostgreSQL

## Installation

### Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js and npm
- Git

### Quick Setup

The project includes a setup script that automates the entire installation process:

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

### Start Development Server

```bash
npm run dev
```

Or use the composer dev script to start all services:

```bash
composer run dev
```

This will start the Laravel server, queue worker, log watcher, and Vite dev server concurrently.

## Usage

### Creating an Account

1. Navigate to Accounts page
2. Click "Create account"
3. Select account type, name, currency, and starting balance
4. Save the account

### Adding Transactions

1. Click on an account to view its details
2. Click "Add transaction"
3. Enter transaction details (date, payee, amount, category, note)
4. Use negative values for expenses, positive for income

### Managing Teams

1. Go to team settings
2. Invite members by email
3. Assign appropriate roles
4. Team members will receive an email invitation

## Testing

Run the test suite:

```bash
php artisan test
```

Run specific tests:

```bash
php artisan test tests/Feature/TransactionTest.php
```

Run tests with coverage:

```bash
php artisan test --coverage
```

## Code Style

Run Laravel Pint to format code:

```bash
vendor/bin/pint --dirty
```

## Project Structure

- `app/Models/` - Eloquent models (Account, Transaction, Category, Team, User)
- `app/Policies/` - Authorization policies
- `resources/views/pages/` - Livewire page components
- `resources/views/components/` - Reusable Blade components
- `database/migrations/` - Database migrations
- `tests/Feature/` - Feature tests
- `tests/Unit/` - Unit tests

## Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Run tests and linting:

```bash
php artisan test
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
