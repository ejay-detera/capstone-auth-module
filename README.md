# Capstone Authentication Module

The Capstone Authentication Module is a robust, decoupled identity management and access control system. It provides a centralized service for user authentication, authorization, and session management, designed for integration into distributed application ecosystems.

## Overview

Identity management is a fundamental component of modern software architecture. This project provides a secure, scalable, and standardized authentication service. The system is architected as a decoupled solution comprising a backend service (auth-service) and a frontend administrative interface (web-interface).

### Core Objectives
- Provide a secure and standardized authentication API.
- Enable seamless user management via a modern web interface.
- Implement production-grade infrastructure using containerization.
- Support scalable data management through MySQL and Redis.

## Documentation Hub

Detailed guides regarding the configuration, development, and contribution processes are available in the documentation directory:

- **[Quickstart Guide](./docs/Quickstart.md)**: Procedures for local environment initialization.
- **[Developer Onboarding](./docs/Onboarding.md)**: Technical overview of the architecture and development practices.
- **[Coding Guidelines](./docs/CodingGuidelines.md)**: Standards for code quality, scalability, and consistency.
- **[Contributing Guide](./docs/Contributing.md)**: Protocols for issue reporting and pull request submission.

## Features

### Identity Management
- User account registration and lifecycle management.
- Secure authentication and session workflows.
- Planned: Automated password recovery and account verification.

### Security
- Token-based authentication for stateless API interactions.
- Cryptographic protection of sensitive data at rest.
- Cross-Origin Resource Sharing (CORS) and security header enforcement.

### Infrastructure
- Comprehensive containerization via Docker and Docker Compose.
- Automated service orchestration and dependency management.
- Integrated caching layer for optimized performance.

## Tech Stack

### Backend
- **Framework**: Laravel 13.x (PHP 8.3)
- **Database**: MySQL 8.0
- **Caching/Queue**: Redis (Alpine)
- **Web Server**: Nginx

### Frontend
- **Framework**: Vue 3 (Composition API)
- **Build Tool**: Vite
- **Language**: TypeScript

### Infrastructure
- **Orchestration**: Docker Compose
- **Process Management**: Supervisor

## System Architecture

The project utilizes a decoupled client-server architecture to ensure strict separation of concerns.

- **Frontend (web-interface)**: A standalone Single Page Application (SPA) communicating via RESTful APIs.
- **Backend (auth-service)**: A Laravel-based REST API handling business logic and security enforcement.
- **Persistence Layer**: MySQL for structured data storage.
- **Cache Layer**: Redis for session persistence and performance optimization.

## Installation Guide

### Prerequisites
- Docker Desktop
- Git

### Environment Configuration

1. Clone the repository:
   ```bash
   git clone https://github.com/your-repo/capstone-auth-module.git
   cd capstone-auth-module
   ```

2. Initialize backend environment variables:
   ```bash
   cd auth-service
   cp .env.example .env
   cd ..
   ```

### Deployment via Docker

1. Build and initialize services:
   ```bash
   docker compose up -d --build
   ```

2. Generate the application encryption key:
   ```bash
   docker exec -it auth-service php artisan key:generate
   ```

3. Execute database migrations:
   ```bash
   docker exec -it auth-service php artisan migrate
   ```

## Running the Project

### Service Access
- **Frontend**: [http://localhost:8080](http://localhost:8080)
- **Backend API**: [http://localhost:8000](http://localhost:8000)

### Administration
- **Log Monitoring**: `docker compose logs -f`
- **Service Termination**: `docker compose down`
- **Container Access**: `docker exec -it auth-service sh`

## Project Structure

```text
.
├── auth-service/          # Laravel Backend Application
│   ├── app/               # Application logic
│   ├── bootstrap/         # Initialization
│   ├── config/            # Configuration
│   ├── database/          # Migrations and schema
│   ├── public/            # Nginx entry point
│   └── routes/            # API definitions
├── web-interface/         # Vue Frontend Application
│   ├── src/               # Source code
│   │   ├── components/    # UI components
│   │   └── assets/        # Static resources
│   └── public/            # Public assets
└── docker-compose.yml     # Service orchestration
```

## Security Standards

### Data Protection
- **Encryption**: Sensitive data is protected using AES-256-CBC via Laravel's encryption services.
- **Hashing**: Password security is enforced using Argon2 or Bcrypt.

### Network Security
- **Reverse Proxy**: Nginx handles request routing and SSL termination (if configured).
- **Isolation**: Internal services are isolated within a private Docker network.

## Testing Strategy

The project employs PHPUnit for backend verification and Vitest for frontend validation.

### Backend Execution
```bash
docker exec -it auth-service php artisan test
```

## Troubleshooting

### Missing Application Key
If a `MissingAppKeyException` occurs, execute:
```bash
docker exec -it auth-service php artisan key:generate
```

### Database Connectivity
Verify the status of the database container:
```bash
docker compose ps
```

## License

This project is licensed under the MIT License.

## Authors

- **Antigravity Engineering** - *System Architecture and Infrastructure*
