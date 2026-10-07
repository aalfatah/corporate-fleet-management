# Corporate Fleet Management

A role-based fleet management system for organizations that need to manage vehicle bookings, driver assignments, trip lifecycle tracking, and departmental access control.

This project is built with Laravel, Vue 3, Inertia.js, and Vite, and is designed to support internal operations such as booking approvals, vehicle availability checks, and admin management.

## Features

- Vehicle booking requests and trip lifecycle management
- Role-based approval flow for PIC and super admin users
- Driver status tracking from trip start to completion
- Vehicle and driver management screens
- Department and user administration
- Profile and password self-service for authenticated users
- Dashboard for operational visibility
- Responsive front-end built with Vue and Tailwind

## Tech Stack

- Laravel 13
- PHP 8.3
- Vue 3
- Inertia.js
- Vite
- Tailwind CSS
- SQLite by default for local development

## Project Structure

```text
app/
  Http/Controllers/      # Business logic and request handling
  Models/                # Eloquent models
config/                  # Laravel configuration
database/
  migrations/            # Database schema updates
  seeders/               # Seed data for local setup
public/                  # Web entrypoint and static assets
resources/
  js/                    # Vue/Inertia frontend
  views/                 # Blade templates
routes/
  web.php                # Application routes
storage/
  app/                   # Local application storage
  logs/                  # Logs
tests/                   # PHPUnit tests
.env.example             # Example environment configuration
composer.json            # PHP dependencies and scripts
package.json             # Frontend dependencies and scripts
vite.config.js          # Vite configuration
```

## Roles and Access

The application includes a permission model based on user roles:

- Guest: login access only
- Authenticated user: can manage profile and access dashboard
- PIC: can approve or reject bookings
- Super Admin: manages users, departments, vehicles, and drivers
- Driver: can start and complete trip logs for assigned bookings

## Getting Started

### Prerequisites

- PHP 8.3+
- Composer
- Node.js 18+
- npm

### Installation

1. Clone the repository

```bash
git clone https://github.com/aalfatah/corporate-fleet-management.git
cd corporate-fleet-management
```

2. Install PHP dependencies

```bash
composer install
```

3. Create your environment file

```bash
cp .env.example .env
php artisan key:generate
```

4. Configure your database in `.env` if needed

The project includes SQLite as the default local configuration in `.env.example`.

5. Run database migrations

```bash
php artisan migrate
```

Optional: seed the database if seeders are available for your environment.

6. Install JavaScript dependencies and build frontend assets

```bash
npm install
npm run build
```

7. Start the local development server

```bash
php artisan serve
```

Then open the app in your browser at:

```text
http://localhost:8000
```

## Development Commands

Run the Laravel app in development mode:

```bash
composer run dev
```

Run tests:

```bash
php artisan test
```

Build frontend assets for production:

```bash
npm run build
```

## Notes

- This project is intended for internal corporate fleet operations and workflow-based approvals.
- The default stack is optimized for a Laravel monolith with a Vue-based UI layer.
- The app can be adapted for additional fleet logic such as maintenance tracking, fuel monitoring, or audit reporting.

## License

This project is licensed under the MIT License.
