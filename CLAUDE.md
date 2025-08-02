# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel 12 multi-tenant booking application for barber services. The application uses Spatie's Laravel Multitenancy package to handle tenant isolation with separate databases per tenant.

## Key Architecture

**Multi-tenancy Structure:**
- **Landlord Database**: Contains tenant configuration (`tenants` table with `name`, `domain`, `database` fields)
- **Tenant Databases**: Each tenant has their own database containing business data (barbers, services, bookings)
- **Tenant Isolation**: API routes are protected with `tenant` and `auth:sanctum` middleware

**Core Models:**
- `Tenant` (landlord): Manages tenant configuration and database connections
- `Barber` (tenant): Individual barber profiles, linked to tenant
- `Service` (tenant): Available services (haircuts, etc.)
- `BarberService` (tenant): Pivot model for barber-service relationships

**Migration Strategy:**
- Landlord migrations in `database/migrations/landlord/` 
- Tenant migrations in `database/migrations/` (main directory)
- All tenant tables include `tenant_id` foreign key for data isolation

## Development Commands

**Testing:**
```bash
# Run all tests with Pest
composer test
# or
php artisan test

# Run specific test file
php artisan test tests/Feature/Models/BarberTest.php

# Run specific test method
php artisan test --filter="test_method_name"
```

**Code Quality:**
```bash
# Format code with Laravel Pint
./vendor/bin/pint

# Run database migrations
php artisan migrate
php artisan migrate:status
```

**Development Server:**
```bash
# Start full development environment (server + queue + logs + vite)
composer dev

# Individual services:
php artisan serve          # Web server
php artisan queue:listen    # Queue worker
php artisan pail           # Log viewer
npm run dev                # Vite asset compilation
```

**Asset Building:**
```bash
npm run build    # Production build
npm run dev      # Development build with watch
```

**Multi-tenancy Commands:**
```bash
# Create new tenant
php artisan tenant:create

# Run artisan commands for specific tenant
php artisan tenant:artisan {tenant_id} {command}

# Migrate all tenants
php artisan tenants:migrate
```

## Testing Configuration

- Uses **Pest PHP** for testing framework
- Test database configured as `testing` in phpunit.xml
- Feature tests include `RefreshDatabase` trait automatically
- Tests located in `tests/Feature/` and `tests/Unit/`

## Frontend Stack

- **Vite** for asset bundling and hot reloading
- **Tailwind CSS 4.0** for styling
- **Laravel Mix** integration through `laravel-vite-plugin`

## Database Strategy

The application uses a database-per-tenant approach:
- Each tenant gets a unique database specified in the `tenants.database` field
- Tenant switching happens automatically based on request context
- No shared tables between tenants (complete data isolation)