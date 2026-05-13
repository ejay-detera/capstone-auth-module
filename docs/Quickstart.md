# Quickstart Guide

This guide provides the necessary procedures to initialize the **Capstone Auth Module** in a local development environment.

---

## Prerequisites

The following software must be installed on the host system:

- **Docker Desktop**
- **Git**
- **Node.js** (v20 or higher)

---

## Initial Setup

Follow these steps to initialize and launch the platform:

### 1. Repository Acquisition
```bash
git clone https://github.com/your-repo/capstone-auth-module.git
cd capstone-auth-module
```

### 2. Environment Configuration
Initialize the backend environment configuration:
```bash
cd auth-service
cp .env.example .env
cd ..
```

### 3. Service Orchestration
Execute the following command to build and start the service containers (Backend, Frontend, MySQL, Redis):
```bash
docker compose up -d --build
```

### 4. Application Initialization
Execute the setup commands within the backend container once the services are operational:

```bash
# Generate application encryption key
docker exec -it auth-service php artisan key:generate

# Execute database schema migrations
docker exec -it auth-service php artisan migrate
```

---

## Service Endpoints

| Component | URL | Description |
|-----------|-----|-------------|
| **Web Interface** | [http://localhost:8080](http://localhost:8080) | Vue 3 Frontend Application |
| **Auth Service** | [http://localhost:8000](http://localhost:8000) | Laravel Backend API |
| **System Health** | [http://localhost:8000/up](http://localhost:8000/up) | Backend availability endpoint |

---

## Verification

To verify the installation:
1. Access [http://localhost:8080](http://localhost:8080) via a web browser.
2. In the event of failure, inspect the service logs:
   ```bash
   docker compose logs -f
   ```

---

## Technical Documentation
- Architectural overview: **[Developer Onboarding](./Onboarding.md)**
- Engineering standards: **[Coding Guidelines](./CodingGuidelines.md)**
- Contribution protocols: **[Contributing Guide](./Contributing.md)**
