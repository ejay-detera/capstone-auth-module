# Coding Guidelines

This document establishes the standards for code quality, scalability, and consistency within the **Capstone Auth Module** project.

---

## General Standards

### 1. Code Readability
- **Self-Documenting Code**: Prioritize intuitive variable and function naming over inline comments.
- **Naming Conventions**:
    - **Backend (PHP/Laravel)**: Adherence to PSR-12; `camelCase` for variables and methods; `PascalCase` for classes.
    - **Frontend (TypeScript/Vue)**: `camelCase` for variables and functions; `PascalCase` for components and classes.

### 2. Documentation
- Utilize **DocBlocks** (`/** ... */`) for complex logic or non-obvious algorithms.
- Documentation should explain the underlying rationale ("why") rather than the implementation details ("what").

---

## Backend Development

### 1. Scalability and Performance
- **Eager Loading**: Prevent N+1 query issues through the strict use of `with()`.
- **Caching Strategy**: Utilize Redis for expensive operations and frequently accessed data.
- **Asynchronous Processing**: Offload long-running tasks to background queues.

### 2. Implementation Consistency
- Adhere to the **Layered Architecture** defined in the [Onboarding Guide](./Onboarding.md).
- Use **Form Request** classes for input validation.
- Utilize **Eloquent Resources** for standardized API response formatting.

---

## Project Organization

- Maintain a feature-based directory structure where possible.
- Ensure every file has a single, well-defined responsibility.
- Avoid deep directory nesting to maintain codebase navigability.

---

## Technical Collaboration

### 1. Issue Management
- Utilize the standard issue template.
- Provide a concise title, detailed description, and reproduction steps for bug reports.
- Apply appropriate labels (e.g., `bug`, `feature`, `documentation`).

### 2. Branch Naming Conventions
Branches must utilize the following prefixes:
- `feat/`: New functionality
- `fix/`: Bug resolution
- `docs/`: Documentation updates
- `refactor/`: Code restructuring

**Example**: `feat/user-authentication`

### 3. Commit Standards
The project follows the **Conventional Commits** specification:
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Formatting and non-functional changes

**Format**: `<type>: <description>`

---

## Related Documentation
- **[Contributing Guide](./Contributing.md)** – Pull Request protocols.
- **[Developer Onboarding](./Onboarding.md)** – Architectural specifications.
