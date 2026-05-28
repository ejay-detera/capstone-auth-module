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
- **[Docker Setup Guide](./DOCKER.md)**: Comprehensive guide on container orchestration, setup, and troubleshooting.
- **[Developer Onboarding](./docs/Onboarding.md)**: Technical overview of the architecture and development practices.
- **[Coding Guidelines](./docs/CodingGuidelines.md)**: Standards for code quality, scalability, and consistency.
- **[Contributing Guide](./docs/Contributing.md)**: Protocols for issue reporting and pull request submission.

## Features

### Identity Management

- User account registration and lifecycle management.
- Secure authentication and session workflows.
- Automated password recovery and email verification.
- User profile updates and role/permission enforcement.

### Security

- Token-based authentication with secure JWT and refresh token rotation.
- AES-256-CBC payload encryption on write requests (POST, PUT, PATCH) between frontend and backend.
- Cryptographic protection of sensitive data at rest.
- Cross-Origin Resource Sharing (CORS) and security header enforcement.

### Infrastructure

- Comprehensive containerization via Docker and Docker Compose.
- Shared Nginx reverse proxy gateway handling request routing.
- Automated service orchestration and dependency management.
- Integrated caching layer (Redis) for session and token management.

## Tech Stack

### Backend

- **Framework**: Laravel 13.x (PHP 8.3)
- **Database**: MySQL 8.0
- **Caching/Queue**: Redis (Alpine)
- **Web Server**: Nginx (Development PHP CLI server)

### Frontend

- **Framework**: Vue 3 (Composition API)
- **Styling**: Tailwind CSS & Shadcn Vue
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
- Node.js (v20 or higher, for local dev outside containers)

### Environment Configuration

1. Clone the repository:

   ```bash
   git clone https://github.com/your-repo/capstone-auth-module.git
   cd capstone-auth-module
   ```

2. Setup the external Docker network. The services in `docker-compose.yml` communicate over an external network called `shared-capstone-network`. Create it by running:
   ```bash
   docker network create shared-capstone-network
   ```

3. Initialize environment variables. Copy the templates and configure the parameters (especially database credentials and `INTERNAL_ENCRYPTION_KEY` which must be a 32-character string):
   ```bash
   # Root / Docker Compose env
   cp .env.example .env

   # Backend env
   cd auth-service
   cp .env.example .env
   cd ..
   ```

### Deployment via Docker

For a detailed setup guide, command reference, and common troubleshooting steps (especially for socket/connection errors), refer to the **[Docker Setup & Management Guide](./DOCKER.md)**.

1. Build and initialize services:

   ```bash
   docker compose up -d --build
   ```

2. Generate the application encryption key:

   ```bash
   docker exec -it auth-service php artisan key:generate
   ```

3. Execute database migrations and seed default users, roles, and permissions:
   ```bash
   docker exec -it auth-service php artisan migrate --seed
   ```

## Running the Project

### Service Access

- **Web Interface (via Gateway)**: [http://localhost:5173](http://localhost:5173) (Routes requests to the Vue UI at `/` and backend API at `/api`)
- **Backend API (Direct)**: [http://localhost:8000](http://localhost:8000)

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

- **Payload Encryption**: Write operations (POST, PUT, PATCH) encrypt parameters with AES-256-CBC client-side before transmission. The backend decrypts payloads via `PayloadSecurityMiddleware` and validates the input.
- **Data Encryption**: Sensitive database values are protected using AES-256-CBC via Laravel's encryption services.
- **Hashing**: Password security is enforced using Argon2 or Bcrypt.

### Network Security

- **Reverse Proxy**: Nginx proxy gateway routes public traffic, mapping domain paths to internal services.
- **Isolation**: Internal services are isolated within the `shared-capstone-network` private network.

## Testing Strategy

The project employs PHPUnit for backend API and feature verification.

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

### Encryption Key Mismatch

Ensure `INTERNAL_ENCRYPTION_KEY` in the root `.env` (passed to `web-interface`) and `auth-service/.env` matches exactly and is 32 characters long. If decryption errors occur, restart the containers.

### Database Connectivity

Verify the status of the database container:

```bash
docker compose ps
```

## License

This project is licensed under the MIT License.
