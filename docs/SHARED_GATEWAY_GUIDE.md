# Shared Gateway & Nginx Reverse Proxy — Integration Guide

> **Maintained by:** Auth Module Team  
> **Last Updated:** 2026-05-27  
> **Nginx Entry Point:** `http://localhost:5173`

This guide explains how to integrate your subsystem (frontend **and** backend API) into the **Shared Nginx Gateway** so that every capstone project runs behind a single entry point with zero port conflicts.

---

## Table of Contents

1. [Architecture Overview](#1-architecture-overview)
2. [How the Reverse Proxy Works](#2-how-the-reverse-proxy-works)
3. [Benefits](#3-benefits)
4. [Prerequisites](#4-prerequisites)
5. [Step-by-Step: Integrating Your Frontend](#5-step-by-step-integrating-your-frontend)
6. [Step-by-Step: Integrating Your Backend API](#6-step-by-step-integrating-your-backend-api)
7. [Configuring Your Vite Dev Server](#7-configuring-your-vite-dev-server)
8. [Updating Your Frontend API Calls](#8-updating-your-frontend-api-calls)
9. [Current Route Map & Naming Convention](#9-current-route-map--naming-convention)
10. [Applying Changes & Restarting](#10-applying-changes--restarting)
11. [Real-World Example: SERMS Integration](#11-real-world-example-serms-integration)
12. [Troubleshooting](#12-troubleshooting)
13. [Quick-Start Checklist](#13-quick-start-checklist)

---

## 1. Architecture Overview

All capstone subsystems share a single Nginx reverse proxy container (`shared-nginx-proxy`) that listens on **port 5173**. The proxy routes incoming requests to the correct Docker container based on the **URL path prefix**.

```
                          ┌──────────────────────────────────────────────────────┐
                          │         Docker: shared-capstone-network              │
                          │                                                      │
  Browser ──► :5173 ──►   │  ┌────────────────┐                                  │
                          │  │  Nginx Gateway  │                                  │
                          │  │  (nginx-proxy)  │                                  │
                          │  └───────┬─────────┘                                  │
                          │          │                                             │
                          │  ┌───────┴─────────────────────────────────┐          │
                          │  │           Path-based Routing             │          │
                          │  ├─────────────────┬───────────────────────┤          │
                          │  │                 │                       │          │
                          │  ▼                 ▼                       ▼          │
                          │ /                 /serms/               /crms/        │
                          │ /api/             /api/serms/           /api/crms/    │
                          │  │                 │                       │          │
                          │  ▼                 ▼                       ▼          │
                          │ web-interface    serms_web              crms-web      │
                          │ auth-service     serms_api              crms-api(s)   │
                          │                                                      │
                          └──────────────────────────────────────────────────────┘
```

---

## 2. How the Reverse Proxy Works

1. The **user** opens `http://localhost:5173/<path>` in their browser.
2. **Nginx** receives the request and matches the URL path against its `location` blocks.
3. Nginx **forwards** the request to the correct internal Docker container using its **container name** (e.g., `http://serms_api:8000`).
4. The internal container processes the request and sends the response **back through Nginx** to the browser.

> **Key insight:** Containers communicate using Docker's internal DNS. Nginx resolves `serms_api` to the container's internal IP automatically — no host ports required.

---

## 3. Benefits

| Benefit | Without Proxy | With Proxy |
|:---|:---|:---|
| **Access URLs** | `localhost:5000`, `localhost:5001`, `localhost:5002`, `localhost:8000`, `localhost:8005`... | `localhost:5173` for everything |
| **Port conflicts** | Every subsystem must pick a unique host port, conflicts are common | No host ports needed for app containers |
| **CORS issues** | Frontend on `:5002` calling API on `:8005` = cross-origin = CORS config needed | Same origin (`localhost:5173`) = no CORS |
| **Environment variables** | `VITE_API_URL=http://localhost:8005` (changes per environment) | `fetch('/api/serms/...')` (works everywhere) |
| **Production parity** | Dev and prod behave differently | Dev mirrors production routing exactly |
| **Security** | API ports exposed to the host machine | API containers are internal-only |

---

## 4. Prerequisites

Before you begin, make sure:

1. **Docker Desktop** is running.
2. The **shared Docker network** exists. If not, create it once:

   ```bash
   docker network create shared-capstone-network
   ```

3. The **auth-module** project is running (it owns the Nginx proxy):

   ```bash
   cd capstone-auth-module
   docker compose up -d
   ```

4. You have access to edit `capstone-auth-module/nginx/nginx.conf`.

---

## 5. Step-by-Step: Integrating Your Frontend

### 5.1 — Join the shared network

In your project's `docker-compose.yml`, add `shared-capstone-network` to your **frontend** service:

```yaml
services:
  my-frontend:
    build:
      context: ./frontend
      dockerfile: Dockerfile
    container_name: my_frontend          # ← Nginx will use this name
    # ports:                             # ← REMOVE or comment out host ports
    #   - "5003:5003"                    #    (Nginx handles external access)
    volumes:
      - ./frontend:/app
      - /app/node_modules
    networks:
      - my_internal_network
      - shared-capstone-network          # ← ADD this
    command: npm run dev -- --host --port 5003

networks:
  my_internal_network:
    driver: bridge
  shared-capstone-network:
    external: true                       # ← Must be external
```

> **Note:** You can optionally keep `ports` during transition for direct access, but the goal is to remove them and rely solely on the proxy.

### 5.2 — Add a frontend location block to Nginx

Open `capstone-auth-module/nginx/nginx.conf` and add a new `location` block inside the `server { }`:

```nginx
    # My Subsystem frontend at /my-system/
    location /my-system/ {
        set $upstream_my http://my_frontend:5003;
        proxy_pass $upstream_my;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
    }
```

**Why `set $upstream`?** Using a variable makes Nginx resolve the DNS at request time, not at startup. This means Nginx won't crash if your subsystem isn't running yet.

### 5.3 — Set `base` in Vite config

Because your app is served under a sub-path (e.g., `/my-system/`), Vite needs to know this so that assets, routes, and links are generated correctly:

```js
// vite.config.js
export default defineConfig({
  base: '/my-system/',   // ← Must match your Nginx location path
  // ...
})
```

---

## 6. Step-by-Step: Integrating Your Backend API

### 6.1 — Join the shared network

In your project's `docker-compose.yml`, add `shared-capstone-network` to your **backend API** service and **remove its host port mapping**:

```yaml
services:
  my-api:
    build:
      context: ./api
      dockerfile: Dockerfile
    container_name: my_api               # ← Nginx will use this name
    # ports:                             # ← REMOVE host ports entirely
    #   - "8006:8000"                    #    The API is now internal-only
    volumes:
      - ./api:/var/www
    networks:
      - my_internal_network
      - shared-capstone-network          # ← ADD this
    environment:
      - DB_HOST=mysql
      # ...
```

### 6.2 — Add a backend API location block to Nginx

Open `capstone-auth-module/nginx/nginx.conf` and add a new `location` block for your API.

**Important:** The `/api/my-system/` location block must appear **before** the generic `/api/` block in the config file. Nginx uses **longest prefix match** for `location` directives, so in practice order doesn't matter for prefix matching — but placing specific paths first improves readability and avoids confusion.

```nginx
    # My Subsystem API gateway
    location /api/my-system/ {
        proxy_pass http://my_api:8000/api/;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header Content-Type $content_type;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_pass_request_body on;
    }
```

### Understanding path rewriting

The `proxy_pass` directive with a URI (`/api/`) performs **automatic path rewriting**:

| Browser requests | Nginx forwards to container |
|:---|:---|
| `GET /api/my-system/users` | `GET /api/users` on `my_api:8000` |
| `POST /api/my-system/orders` | `POST /api/orders` on `my_api:8000` |

Nginx strips `/api/my-system/` and replaces it with `/api/`. This means **your Laravel routes stay exactly the same** — no changes needed in `routes/api.php` or your service providers.

---

## 7. Configuring Your Vite Dev Server

Your Vite development server runs **inside** the Docker container, and it needs its own proxy configuration so that API calls from the browser (during `npm run dev`) are forwarded to your backend container.

### Why is this needed?

During development, the browser loads your app from the Vite dev server (e.g., `serms_web:5002`). When the app calls `fetch('/api/serms/expenses')`, Vite intercepts the request and proxies it to the backend container. Without this, the browser would try to load `/api/serms/expenses` from the Vite server itself and get a 404.

### Configuration

```js
// vite.config.js (or vite.config.ts)
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  base: '/my-system/',                   // Must match Nginx location path
  plugins: [vue()],
  server: {
    watch: {
      usePolling: true,                  // Required inside Docker
      interval: 100
    },
    proxy: {
      '/api/my-system': {                // Intercept calls matching this prefix
        target: 'http://my-api:8000',    // Forward to backend container
        changeOrigin: true,
        secure: false,
        rewrite: (path) => path.replace(/^\/api\/my-system/, '/api')
      }
    }
  }
})
```

### Breakdown of each option

| Option | Value | Purpose |
|:---|:---|:---|
| `base` | `'/my-system/'` | Tells Vite that the app lives under this sub-path |
| `proxy key` | `'/api/my-system'` | Matches any request starting with `/api/my-system` |
| `target` | `'http://my-api:8000'` | The Docker container name + port of your backend |
| `changeOrigin` | `true` | Sets the `Host` header to match the target |
| `rewrite` | `path.replace(...)` | Strips `/api/my-system` → `/api` so Laravel receives the correct path |

> **Note:** `target` uses the Docker **service name** (from `docker-compose.yml`), not `localhost`. Inside Docker, containers resolve each other by service name.

---

## 8. Updating Your Frontend API Calls

After configuring the proxy, update **every** `fetch()` or `axios` call in your frontend to use the new prefixed path.

### Before (direct port access)

```js
// ❌ Old way — hardcoded port, breaks in production
const response = await fetch('http://localhost:8006/api/users')
```

### After (proxy-aware, relative path)

```js
// ✅ New way — works in dev AND production
const response = await fetch('/api/my-system/users', {
  headers: {
    'Accept': 'application/json',
    'Authorization': `Bearer ${token}`
  }
})
```

### Tips for a clean migration

1. **Search your codebase** for all API call patterns:
   ```bash
   grep -rn "/api/" src/ --include="*.js" --include="*.ts" --include="*.vue"
   ```

2. **Use a centralized API client** (recommended). Create a single `api.js` utility:
   ```js
   // src/lib/api.js
   const API_PREFIX = '/api/my-system'

   export async function apiFetch(endpoint, options = {}) {
     const url = `${API_PREFIX}${endpoint}`
     const response = await fetch(url, {
       headers: {
         'Accept': 'application/json',
         'Authorization': `Bearer ${getToken()}`,
         ...options.headers
       },
       ...options
     })
     if (!response.ok) throw new Error(`API Error: ${response.status}`)
     return response.json()
   }
   ```

   Then use it everywhere:
   ```js
   import { apiFetch } from '@/lib/api'

   const users = await apiFetch('/users')            // → /api/my-system/users
   const order = await apiFetch('/orders', {
     method: 'POST',
     body: JSON.stringify(data)
   })
   ```

3. **Do NOT change your Laravel routes.** The Nginx `proxy_pass` rewrite handles the path transformation. Your `routes/api.php` files stay exactly as they are.

---

## 9. Current Route Map & Naming Convention

### Active routes

| Path Prefix | Type | Proxies To | Subsystem |
|:---|:---|:---|:---|
| `/` | Frontend | `web-interface:5000` | Auth Module |
| `/api/` | Backend | `auth-service:8000/api/` | Auth Module |
| `/serms/` | Frontend | `serms_web:5002` | SERMS |
| `/api/serms/` | Backend | `serms_api:8000/api/` | SERMS |
| `/crms/` | Frontend | `crms-web:5001` | CRMS |

### Naming convention

When adding a new subsystem, follow this pattern:

| Component | Path Pattern | Example |
|:---|:---|:---|
| Frontend | `/<subsystem-slug>/` | `/inventory/` |
| Backend API | `/api/<subsystem-slug>/` | `/api/inventory/` |

For **microservice** architectures with multiple backends, use a nested pattern:

| Component | Path Pattern | Example |
|:---|:---|:---|
| Vendor service | `/api/crms/vendor/` | Proxies to `vendor-management:8000` |
| Contract service | `/api/crms/contract/` | Proxies to `contract-management:8000` |

### Reserved paths

These paths are already taken and must not be reused:

- `/` — Auth Module frontend
- `/api/` — Auth Module API
- `/serms/` and `/api/serms/` — SERMS
- `/crms/` — CRMS frontend

---

## 10. Applying Changes & Restarting

After making changes to `nginx.conf` and your `docker-compose.yml`, apply them:

```bash
# 1. Restart your subsystem containers (from your project directory)
cd your-project
docker compose up -d

# 2. Restart the Nginx proxy (from the auth-module directory)
cd capstone-auth-module
docker compose restart nginx-proxy
```

> **Important:** You must restart the `nginx-proxy` container after editing `nginx.conf`. The config file is mounted as a volume, but Nginx only reads it on startup.

### Verifying it works

```bash
# Test that Nginx can reach your frontend
curl -I http://localhost:5173/my-system/

# Test that Nginx can reach your backend API
curl http://localhost:5173/api/my-system/health

# Check if your container is on the shared network
docker network inspect shared-capstone-network | grep "my_api"
```

---

## 11. Real-World Example: SERMS Integration

Here is exactly what was changed to integrate the SERMS subsystem (a **modular monolith** with a single backend) into the shared gateway.

### 11.1 — Nginx config (`nginx.conf`)

Added two location blocks — one for the frontend, one for the backend:

```nginx
    # SERMS API gateway
    location /api/serms/ {
        proxy_pass http://serms_api:8000/api/;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header Content-Type $content_type;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_pass_request_body on;
    }

    # SERMS frontend at /serms/
    location /serms/ {
        set $upstream_serms http://serms_web:5002;
        proxy_pass $upstream_serms;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
    }
```

### 11.2 — Docker Compose (`docker-compose.yml`)

```diff
  api:
    build:
      context: ./apps/api
      dockerfile: Dockerfile
    container_name: serms_api
    restart: unless-stopped
-   ports:
-     - "8005:8000"              # Removed — no longer exposed to host
    volumes:
      - ./apps/api:/var/www
    depends_on:
      - mysql
    networks:
      - serms_network
+     - shared-capstone-network  # Added — so Nginx can reach this container
    environment:
      - DB_HOST=mysql
      # ...
```

### 11.3 — Vite config (`vite.config.js`)

```diff
  server: {
    proxy: {
-     '/api': {
-       target: 'http://api:8000',
-       changeOrigin: true,
-       secure: false
+     '/api/serms': {
+       target: 'http://api:8000',
+       changeOrigin: true,
+       secure: false,
+       rewrite: (path) => path.replace(/^\/api\/serms/, '/api')
      }
    }
  }
```

### 11.4 — Frontend API calls (`expense.js`)

```diff
- const response = await fetch('/api/expenses', { ... })
+ const response = await fetch('/api/serms/expenses', { ... })

- await fetch(`/api/expenses/${id}`, { method: 'DELETE' })
+ await fetch(`/api/serms/expenses/${id}`, { method: 'DELETE' })
```

### 11.5 — What did NOT change

- **Laravel routes** (`routes/api.php`, service providers) — unchanged. Nginx handles path rewriting.
- **Laravel controllers** — unchanged.
- **Database configuration** — unchanged.

---

## 12. Troubleshooting

### "502 Bad Gateway"

**Cause:** Nginx can't reach your container.

**Fix:**
1. Check your container is running: `docker ps | grep my_api`
2. Check it's on the shared network: `docker network inspect shared-capstone-network`
3. Check the container name in `proxy_pass` matches exactly.

### "404 Not Found" from your API

**Cause:** The path rewriting isn't matching your Laravel routes.

**Fix:**
1. Check what Nginx is forwarding. A request to `/api/my-system/users` should arrive at your container as `/api/users`.
2. Verify your `proxy_pass` has the trailing slash: `proxy_pass http://my_api:8000/api/;` (the trailing `/` is critical for rewriting).
3. Test directly: `docker exec -it my_api curl http://localhost:8000/api/users`

### "CORS errors" in the browser

**Cause:** You're still calling the API using a direct port (e.g., `http://localhost:8005`) instead of the proxied path.

**Fix:** Update your `fetch()` calls to use the relative path: `/api/my-system/...`

### Nginx won't start (config error)

**Fix:**
```bash
# Validate your nginx.conf syntax
docker exec shared-nginx-proxy nginx -t

# View Nginx error logs
docker logs shared-nginx-proxy
```

### Vite HMR (Hot Module Replacement) not working

**Cause:** WebSocket connections for HMR need the `Upgrade` and `Connection` headers.

**Fix:** Make sure your Nginx location block includes:
```nginx
proxy_set_header Upgrade $http_upgrade;
proxy_set_header Connection "upgrade";
```

### Container not resolving (DNS)

**Cause:** Container was started before the shared network existed.

**Fix:**
```bash
# Recreate your containers
docker compose down
docker compose up -d
```

---

## 13. Quick-Start Checklist

Use this checklist when integrating a new subsystem:

- [ ] **Create the shared network** (one-time): `docker network create shared-capstone-network`
- [ ] **Docker Compose — Frontend:**
  - [ ] Add `shared-capstone-network` to frontend service `networks`
  - [ ] Remove (or keep temporarily) the `ports` mapping
- [ ] **Docker Compose — Backend API:**
  - [ ] Add `shared-capstone-network` to backend service `networks`
  - [ ] Remove the `ports` mapping (API should be internal-only)
- [ ] **Docker Compose — Networks section:**
  - [ ] Declare `shared-capstone-network` as `external: true`
- [ ] **Nginx (`nginx.conf`):**
  - [ ] Add `location /<slug>/` block for the frontend
  - [ ] Add `location /api/<slug>/` block for the backend API
  - [ ] Verify `proxy_pass` uses the correct container name and port
  - [ ] Verify trailing slash on `proxy_pass` URI for path rewriting
- [ ] **Vite config:**
  - [ ] Set `base: '/<slug>/'`
  - [ ] Update `proxy` key from `'/api'` to `'/api/<slug>'`
  - [ ] Add `rewrite` rule to strip the slug prefix
- [ ] **Frontend code:**
  - [ ] Update all `fetch()` / `axios` calls: `/api/...` → `/api/<slug>/...`
  - [ ] (Recommended) Centralize API calls in a single utility module
- [ ] **Restart:**
  - [ ] `docker compose up -d` in your project
  - [ ] `docker compose restart nginx-proxy` in `capstone-auth-module`
- [ ] **Test:**
  - [ ] Open `http://localhost:5173/<slug>/` — frontend loads
  - [ ] Open `http://localhost:5173/api/<slug>/` — API responds
  - [ ] Check browser DevTools Network tab for any failed requests

---

_End of Shared Gateway Integration Guide_
