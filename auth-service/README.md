# Capstone Authentication Service (auth-service)

The **auth-service** is the centralized identity provider and access control gateway for the Capstone application suite. Built on **Laravel 13.x** and running on **PHP 8.3**, this service governs user credentials, session state, permissions, and security policies across all modules.

---

## Key Features

- **Stateless JWT Authentication**: Implements JSON Web Token verification with a robust access/refresh token rotation mechanism.
- **Role-Based Access Control (RBAC)**: Supports dynamic role assignments and system-scoped privileges (e.g., `IT Admin`, `User`).
- **Secure Write Payloads (AES-256-CBC)**: Protects incoming write parameters (POST, PUT, PATCH requests) by decrypting payload data sent from the Vue interface.
- **Account Lifecycles**: Supports user registration, email verification flows, password resets, and account audits.
- **Robust Testing**: Fully covered by backend feature and unit tests via PHPUnit.

---

## Core Architecture & Components

### 1. Payload Security Middleware
The service includes a custom `PayloadSecurityMiddleware` registered globally or on write routes.
- **File**: `app/Http/Middleware/PayloadSecurityMiddleware.php`
- **Behavior**: Inspects incoming request headers. If `X-Encrypted: true` is present, it intercept the raw body, decrypts it using the `EncryptionService` and the `INTERNAL_ENCRYPTION_KEY`, and merges the decrypted parameters back into the Request inputs.
- **Encrypter**: `app/Services/EncryptionService.php` using `aes-256-cbc`.

### 2. User & Authentication Logic
- **Primary Identifier**: Email address (username has been deprecated and removed from database schemas).
- **Controllers**:
  - `AuthController`: Manages token rotation, logouts, password recovery, email verification, and system status check.
  - `AdminUserController`: Enables administration of user accounts, assignments of departments, and roles.
  - `RoleController`: Exposes endpoints for managing custom permission associations and role attributes.

### 3. Database Schema & Seeding
- **Roles & Permissions**: Default structures are defined in `database/seeders/RolePermissionSeeder.php`, initializing permissions for system configuration and user management.
- **Default Admins**: Seeded via `database/seeders/UserSeeder.php`.

---

## Environment Variables

Configure your local `auth-service/.env` file with the following variables:

```ini
APP_NAME="Capstone Auth Service"
APP_ENV=local
APP_KEY=base64:... # Laravel general app key
APP_DEBUG=true
APP_URL=http://localhost

# Shared frontend/backend encryption key (32-characters)
INTERNAL_ENCRYPTION_KEY=your_secure_32_char_key_here

# Database Configuration (matches docker-compose)
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=capstone_auth
DB_USERNAME=capstone_user
DB_PASSWORD=capstone_password

# Redis Configuration (used for caching)
REDIS_HOST=redis
REDIS_PORT=6379

# Mail Server Configurations (for SMTP password resets/verification)
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_smtp_username
MAIL_PASSWORD=your_smtp_password
MAIL_FROM_ADDRESS="no-reply@capstone.local"
MAIL_FROM_NAME="Capstone Identity"
```

---

## Docker Integration & CLI Commands

The service runs inside a Docker container named `auth-service` as part of the orchestration bundle.

### Command Execution inside Container

To run database migrations and populate seeds:
```bash
docker exec -it auth-service php artisan migrate --seed
```

To clear config and cache layers:
```bash
docker exec -it auth-service php artisan config:clear
docker exec -it auth-service php artisan cache:clear
```

To run the complete PHPUnit test suite:
```bash
docker exec -it auth-service php artisan test
```

---

## API Documentation

| Endpoint | Method | Encrypted? | Description |
|---|---|---|---|
| `/api/login` | `POST` | Yes | Validates credentials and returns JWT tokens. |
| `/api/logout` | `POST` | No | Revokes current user session tokens. |
| `/api/refresh` | `POST` | No | Performs refresh token rotation to get a new access token. |
| `/api/forgot-password` | `POST` | Yes | Requests password reset code sent via email. |
| `/api/reset-password` | `POST` | Yes | Updates credentials using a valid reset token. |
| `/api/verify-email` | `POST` | Yes | Verifies user email from the verification token. |
| `/api/admin/users` | `GET` | No | Lists all registered accounts (Admin only). |
| `/api/admin/users` | `POST` | Yes | Provisions a new user account (Admin only). |
```
