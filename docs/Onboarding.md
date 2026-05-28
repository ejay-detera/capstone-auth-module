# Developer Onboarding

This document provides a technical overview of the **Capstone Auth Module** architecture and engineering practices for new contributors.

---

## System Architecture

The platform utilizes a decoupled client-server architecture managed through Docker orchestration.

### 1. Component Overview
- **Backend (auth-service)**: A Laravel 13 API service utilizing a layered architecture pattern.
- **Frontend (web-interface)**: A Vite-powered Vue 3 application.
- **Persistence Layer**: MySQL for structured data and Redis for transient data, sessions, and caching.

### 2. Backend Layered Architecture
The backend is structured into distinct layers to ensure separation of concerns:
- **Controllers**: Manage HTTP request/response cycles and input validation.
- **Services**: Implement core business logic and coordinate repository interactions.
- **Repositories**: Abstract data persistence and retrieval logic.

---

## Engineering Practices

### 1. Dependency Injection
The platform utilizes **Constructor Injection** to facilitate loose coupling and enhanced testability.
- Dependencies are provided as arguments to class constructors.
- This pattern allows for the seamless substitution of implementations, specifically for mocking during testing.

**Example Implementation:**
```php
class UserService {
    public function __construct(
        protected UserRepository $userRepository
    ) {}
}
```

### 2. Error Handling Strategy
The error handling strategy focuses on predictability and centralized management:
- **Exception Throwing**: Logic layers should detect and throw specific exceptions for error conditions.
- **Custom Exceptions**: Domain-specific exceptions should be utilized to provide meaningful error context.
- **Centralized Handling**: Exceptions are processed by a global handler, ensuring consistent JSON responses for all API failures.

### 3. Frontend Development
The frontend is developed using **Vue 3 (Composition API)** and **TypeScript**.
- **Type Definitions**: API responses and internal data structures must be strictly typed.
- **Component Design**: Components should be modular, maintain a single responsibility, and follow a folder-based structure (encapsulating a component and its assets in its own directory).
- **State Management**: Reactive state is managed via Vue's reactivity system or Pinia where appropriate.

---

## Development Workflow

Every contribution must follow the standardized development process:

1.  **Branching**: Initialize a feature branch from the `main` branch.
2.  **Implementation**:
    - Backend API development (Contract-First).
    - Frontend interface and logic implementation.
3.  **Verification**: Execute automated test suites and perform manual UI validation.
4.  **Submission**: Submit a Pull Request following the **[Contributing Guide](./Contributing.md)**.

---

## References
- **[Quickstart Guide](./Quickstart.md)** – Environment initialization.
- **[Coding Guidelines](./CodingGuidelines.md)** – Engineering standards.
