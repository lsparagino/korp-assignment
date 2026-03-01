---
description: Development Workflow
---

ALWAYS follow the workflow when implementing a new feature or fixing a bug. This ensures consistency, quality, and maintainability of the codebase.

1. Plan your tasks, review them with user. Include tests when possible
2. Write code, following the project structure and conventions, and adding server-side logs
3. **ALWAYS test implementations work**:
   - Write tests for logic, components and e2e
   - Use the agent-browser to test like a real user (client runs at localhost:3000, credentials: admin@example.com/password)
4. Stage your changes with `git add` once a feature works
5. Review changes and analyze the need of refactoring