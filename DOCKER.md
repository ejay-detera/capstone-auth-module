# Docker Setup & Management Guide

This document details the configuration, deployment, and troubleshooting processes for containerizing the **Capstone Authentication Module** workspace.

---

## 🛠️ Prerequisites

Before launching the containers, ensure the following applications are installed and configured:

1. **Docker Desktop** (v4.x or higher)
   - Ensure the Docker daemon is running. On Windows, make sure the system tray icon shows Docker as active.
   - If using Windows, it is highly recommended to use the **WSL 2 backend** for optimal performance.
2. **Git**
3. **Node.js** (v20+ if doing any local host tasks)

---

## 🚀 Step-by-Step Setup

Follow these commands in order from the repository root to initialize the environment:

### 1. Start Docker Desktop
Verify that the Docker engine is running. If you get a socket or connection error like `open //./pipe/dockerDesktopLinuxEngine: The system cannot find the file specified`, start or restart your **Docker Desktop** app.

### 2. Configure Environment Variables
Copy the template files and customize your configurations. Make sure the database passwords match, and set a custom 32-character string for `INTERNAL_ENCRYPTION_KEY`.

```powershell
# Copy root configuration
copy .env.example .env

# Copy auth-service configuration
copy auth-service\.env.example auth-service\.env
```

> [!IMPORTANT]
> The `INTERNAL_ENCRYPTION_KEY` in both `.env` and `auth-service/.env` must be identical and exactly **32 characters** long.

### 3. Create the Shared Network
The microservices utilize an external virtual network for isolated service-to-service communication. Run this command once on your host machine:

```bash
docker network create shared-capstone-network
```

### 4. Build and Start Services
Compile the container images and launch the services in detached (background) mode:

```bash
docker compose up -d --build
```

### 5. Initialize the Laravel Application
Once the containers are up, execute the setup tasks inside the backend container:

```bash
# Generate the application key
docker exec -it auth-service php artisan key:generate

# Run database migrations and seed default credentials/permissions
docker exec -it auth-service php artisan migrate --seed
```

---

## 📁 Orchestration Overview

The container ecosystem is composed of the following services:

| Container Name | Internal Service | Port Mapping (Host:Container) | Purpose |
| :--- | :--- | :--- | :--- |
| **shared-nginx-proxy** | Nginx Reverse Proxy | `5173:5173` | Unified entrypoint routing public traffic |
| **web-interface** | Vue 3 UI (Vite) | *Exposed (5000)* | Administrative frontend |
| **auth-service** | Laravel REST API | `8000:8000` | Backend API business logic |
| **queue-worker** | Redis Queue Worker | *N/A* | Background process and mail queue |
| **redis-cache** | Redis Cache | *N/A* | Token/Session caching and queue broker |
| **mysql-db** | MySQL 8.0 | `33066:3306` | Structured relational database |

---

## 🔧 Management Commands

Use these commands for daily development operations:

### View Running Services
```bash
docker compose ps
```

### Monitor Live Logs
```bash
# View logs for all containers
docker compose logs -f

# View logs for a specific container
docker compose logs -f auth-service
```

### Stop the Environment
```bash
# Stops and preserves database/redis volumes
docker compose down

# Stops and removes all volumes (Destructive: resets database)
docker compose down -v
```

### Exec into Containers
```bash
# Access auth-service terminal
docker exec -it auth-service sh

# Access database terminal
docker exec -it mysql-db mysql -u root -p
```

---

## 🚨 Troubleshooting Common Issues

### "The system cannot find the file specified" (Socket Connection Error)
This occurs when the Docker CLI cannot find the Docker daemon socket.
- **Resolution**: Open **Docker Desktop** on Windows and wait until the status bar in the bottom left turns green. If it is already open, restart it.

### Microservice Database Connection Failure
If the `auth-service` container fails to start due to database connection timeout:
- **Resolution**: Run `docker compose ps` to verify that `mysql-db` is running and healthy. If necessary, rebuild it using `docker compose up -d --force-recreate db`.

### Encryption Mismatch Errors
If you see decrypted payload errors when logging in or testing:
- **Resolution**: Double-check that your `INTERNAL_ENCRYPTION_KEY` in root `.env` matches the one in `auth-service/.env` exactly. Restart containers after editing:
  ```bash
  docker compose down && docker compose up -d
  ```
