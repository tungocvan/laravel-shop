# Production Project History

This directory stores project-specific production facts and incident handoff notes used by the `production-docker-debug` skill.

## Resolution

For:

```text
PROJECT_PATH=/opt/projects/<project>
```

resolve:

```text
docs/production/<project>/PRODUCTION_HISTORY.md
```

Read this file after the canonical production documents and before asking the operator to rediscover project topology.

## Evidence classes

Each project history separates:

- **Stable contract**: topology or workflow facts that are expected to remain valid until deliberately changed.
- **Verify at incident start**: service health, current branch/SHA, running image/container and other volatile state.
- **Historical incidents**: proven prior root causes/fixes. They accelerate diagnosis but are not proof that a new incident has the same cause.

Never store secrets, passwords, tokens, APP_KEY values or full `.env` content.

## Update rule

Update project history when a production incident proves a durable fact, changes topology/deployment behavior, or establishes a reusable operational lesson. Do not turn transient uptime, container IDs, temporary IPs or one-off log output into stable facts.

Project history reduces repeated discovery; it never replaces a minimal current health/source verification.
