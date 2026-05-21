# Contributing Guide

This document outlines the protocols for contributing to the **Capstone Auth Module**. Adherence to these guidelines ensures high-quality contributions and efficient review processes.

---

## Creating and Reporting Issues

Before initiating any development work, verify if there is an existing issue tracking the bug, task, or feature request. If no issue exists, you must create a new one to establish context and outline the technical requirements.

### Issue Guidelines

To ensure issues are actionable for reviewers and maintainers, please adhere to the following structure:

#### 1. Title Format
Format the issue title with a clear category prefix:
- **`[Bug] <Description>`** — For unexpected behavior, security vulnerabilities, or errors.
- **`[Feature] <Description>`** — For new functionality, UI/UX enhancements, or changes.
- **`[Task] <Description>`** — For refactoring, dependency updates, CI/CD, or documentation.

#### 2. Body Structure
Provide as much context as possible. Utilize the following template for new issues:

- **Description:** A concise explanation of the issue or proposed enhancement.
- **Steps to Reproduce (Bugs only):** A step-by-step checklist to recreate the behavior.
- **Expected vs. Actual Behavior:** A clear contrast of the intended outcome versus the observed result.
- **Environment:** Relevant version details (e.g., OS, browser, Node.js, database).
- **Supporting Evidence:** Console errors, backend logs, or visual screenshots.

---

## Contribution Workflow

### 1. Issue Reference
Verify that the issue you intend to address is approved and assigned to you. For new features, always discuss the implementation approach within the issue thread before writing code.

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
3. Ensure the Pull Request title includes the associated issue number (e.g., `[#123] Implement authentication service`).
4. Utilize the **Pull Request Template** provided below.
5. Ensure CI validation passes and await peer review.

---

## Pull Request Template

### 1. Title Format
Your Pull Request title must be formatted as follows:
```text
[#<Issue_Number>] <Short, imperative-mood description of the change>
```
*Example:* `[#105] Add session timeout logic to admin layout`

### 2. Body Structure
Please utilize the following markdown structure for the pull request description:

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

**Title:** `[#123] Configure Docker orchestration and standardize docs`

**Body:**
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
- [ ] Pull Request title includes the associated issue number (e.g., `[#123] Title`).
- [ ] Documentation updates are included if applicable.
- [ ] Automated tests are provided or updated.
- [ ] Verification of the build in the local Docker environment.
- [ ] Commits adhere to Conventional Commit standards.

---

## Communication
For technical inquiries or assistance, please comment on the relevant issue or contact the project maintainers.

---

**[Back to Repository](../README.md)**
