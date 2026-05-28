# Capstone Web Interface (web-interface)

The **web-interface** is the user-facing administrative frontend for the Capstone Identity System. Built on **Vue 3 (Composition API)**, **TypeScript**, **Tailwind CSS**, and **Shadcn Vue**, it offers a premium, responsive UI for user sessions and system governance.

---

## Key Features

- **Decoupled Client Architecture**: Connects to the backend (`auth-service`) using an Axios-based HTTP client with request/response interceptors.
- **Client-Side Payload Encryption**: Automatically encrypts write request bodies (POST, PUT, PATCH) with AES-256-CBC using a shared key before transmission.
- **Strict Route Protection**: Restricts administrative routes to users with the `IT Admin` role, auto-redirecting other roles back to the login screen.
- **User Authentication Workflows**: Clean, responsive interfaces for Login, Password Recovery, Reset Password, and Email Verification.
- **IT Administration Control Panel**: Complete sub-modules to manage Users, Roles, system-scoped Permissions, and Departments.

---

## Project Structure & Views

### 1. Authentication Views
- **`LoginView.vue`**: Validates credentials and handles transition to the administrative console.
- **`ForgotPassword.vue`**: Captures user emails to request reset tickets.
- **`ResetPassword.vue`**: Interacts with the backend to update passwords using token validation.
- **`EmailVerification.vue`**: Processes token links to verify user email registrations.

### 2. Administrative Control Panels (`/admin/*`)
- **`AdminLayout.vue`**: Root shell layout featuring sidebar navigation, profile settings, and logouts.
- **`UserList.vue` & `UserCreate.vue`**: User provisioning, filtering, state management, and role assignments.
- **`RoleManagement.vue`**: Management of system roles, naming conventions, and permission mappings.
- **`PermissionManagement.vue`**: Listing and assignment of system-scoped permissions.
- **`DepartmentManagement.vue`**: Department categorization and user membership association.

---

## Security & API Integration

### AES Request Encryption
To prevent middleman modifications, the frontend encrypts sensitive data bodies:
- **Core Files**: `src/lib/encryption.ts` (helper) and `src/lib/api.ts` (Axios interceptor).
- **Behavior**: If the request method is POST, PUT, or PATCH, `api.ts` encrypts `config.data` using `crypto-js` and sets:
  - `X-Encrypted: true`
  - `Content-Type: text/plain`

### Route Guards (`src/router/index.ts`)
Before navigating, the router verifies permissions:
```typescript
const roleName = user?.profile?.role?.name || user?.role || ''
if (requiresAuth && !token) {
  next({ name: 'login' })
} else if (requiresAdmin && roleName !== 'IT Admin') {
  alert('Access Denied: Only IT Admin can access the authentication module interface.')
  localStorage.removeItem('access_token')
  localStorage.removeItem('user')
  next({ name: 'login' })
} else {
  next()
}
```

---

## Environment Variables

Configure your local environment variables in `.env` (or via Docker environment fields):

```ini
# Shared key (32-characters) for payload security
VITE_INTERNAL_ENCRYPTION_KEY=your_secure_32_char_key_here

# Backend target URL for proxy routing in development
VITE_API_URL=http://auth-service:8000
```

---

## Development & Execution Commands

### Running Locally (Node.js installed)

Install dependencies:
```bash
npm install
```

Start the Vite development server:
```bash
npm run dev
```

Build the static distribution files:
```bash
npm run build
```
```
