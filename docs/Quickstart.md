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

### 2. Network Creation
Since the microservices are orchestrated on a shared virtual network, you must create the external Docker network before starting the containers:
```bash
docker network create shared-capstone-network
```

### 3. Environment Configuration
Initialize the root and backend configuration files:
```bash
# Root env (for docker-compose)
cp .env.example .env

# Backend env
cd auth-service
cp .env.example .env
cd ..
```
Open both `.env` files and define a matching 32-character string for the `INTERNAL_ENCRYPTION_KEY` (e.g. `INTERNAL_ENCRYPTION_KEY=12345678901234567890123456789012`). Also configure your database settings if necessary.

### 4. Service Orchestration
Build and start all service containers (auth-service, web-interface, shared-nginx-proxy, database, redis cache, and queue workers):
```bash
docker compose up -d --build
```

### 5. Application Initialization
Once the containers are running, run the following commands inside the backend container:

```bash
# Generate the application encryption key (APP_KEY)
docker exec -it auth-service php artisan key:generate

# Execute database migrations and seed default administrative users/permissions
docker exec -it auth-service php artisan migrate --seed
```

---

## Service Endpoints

| Component | URL | Description |
|-----------|-----|-------------|
| **Web Interface (via Gateway)** | [http://localhost:5173](http://localhost:5173) | Vue 3 Frontend (Admin & login views) |
| **Auth Service (Direct)** | [http://localhost:8000](http://localhost:8000) | Laravel Backend REST API |
| **System Health** | [http://localhost:8000/up](http://localhost:8000/up) | Backend system availability endpoint |

---

## Verification

To verify the installation:
1. Access [http://localhost:5173](http://localhost:5173) in your web browser. You should be greeted by the Login interface.
2. In the event of connection or decryption failures, inspect the service logs:
   ```bash
   docker compose logs -f
   ```

---

## Technical Documentation
- Shared Gateway: **[Shared Nginx Gateway Guide](./SHARED_GATEWAY_GUIDE.md)**
- Architectural overview: **[Developer Onboarding](./Onboarding.md)**
- Engineering standards: **[Coding Guidelines](./CodingGuidelines.md)**
- Contribution protocols: **[Contributing Guide](./Contributing.md)**
