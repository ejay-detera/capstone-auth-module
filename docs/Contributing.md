# Contributing Guide

This document outlines the protocols for contributing to the **Capstone Auth Module**. Adherence to these guidelines ensures high-quality contributions and efficient review processes.

---

## Contribution Workflow

### 1. Issue Identification
Ensure there is an existing issue tracking the proposed change.
- Search the issue tracker for duplicates.
- If no issue exists, create a new one with a detailed description of the objective.

### 2. Branch Initialization
Create a new feature branch from the `main` branch utilizing the standard naming conventions:
```bash
git checkout -b feat/your-feature-name
```
*(Refer to the [Coding Guidelines](./CodingGuidelines.md) for detailed branch naming standards.)*

### 3. Implementation and Commits
- Implement changes according to the established coding standards.
- Provide or update automated tests as required.
- Commit changes following the **Conventional Commits** specification:
```bash
git commit -m "feat: implement authentication service"
```

### 4. Synchronization
Maintain synchronization with the `main` branch to minimize merge conflicts:
```bash
git checkout main
git pull origin main
git checkout feat/your-feature-name
git merge main
```

### 5. Pull Request Submission
1. Push the local branch to the remote repository.
2. Initialize a Pull Request against the `main` branch.
3. Utilize the **Pull Request Template** provided below.
4. Ensure CI validation passes and await peer review.

---

## Pull Request Template

When submitting a Pull Request, please utilize the following structure for the description:

```markdown
### Summary
A concise description of the purpose of this Pull Request.

### Changes
- Itemized list of specific technical modifications.
- Infrastructure or configuration updates.
- Documentation or testing enhancements.

### Testing
- Description of the verification procedures performed.
- Evidence of successful build or test execution (e.g., command output).

### Breaking Changes
- Identify any backward-incompatible modifications or required manual interventions.
```

### Example Submission

```markdown
### Summary
Implementation of Docker orchestration for local development and a comprehensive documentation overhaul.

### Changes
- Added Dockerfiles for auth-service and web-interface.
- Configured docker-compose.yml for MySQL and Redis integration.
- Refactored documentation into a structured hub in the docs/ directory.
- Standardized all README files to a formal, professional tone.

### Testing
- Executed `docker compose up -d --build` successfully.
- Verified database connectivity and application key generation within the container.
- Validated all internal documentation links.

### Breaking Changes
- Local development now requires Docker Desktop.
- Documentation has been moved from the root to the docs/ directory.
```

---

## Pull Request Checklist
- [ ] Compliance with **[Coding Guidelines](./CodingGuidelines.md)**.
- [ ] Documentation updates are included if applicable.
- [ ] Automated tests are provided or updated.
- [ ] Verification of the build in the local Docker environment.
- [ ] Commits adhere to Conventional Commit standards.

---

## Communication
For technical inquiries or assistance, please comment on the relevant issue or contact the project maintainers.

---

**[Back to Repository](../README.md)**
