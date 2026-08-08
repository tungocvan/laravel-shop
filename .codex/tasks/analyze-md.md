# Task: /analyze-md <MarkdownFile>

Analyze and improve one Markdown architecture/specification document.

## Purpose

Use this task for documentation, specification, architecture notes, business analysis, or implementation prompts that need deeper architectural review.

This task analyzes a Markdown document. It does NOT analyze an entire Laravel module unless the document itself requires direct verification against referenced source files.

## 1. Bootstrap

Before analysis, read when present:

```text
.codex/bootstrap/CODEX_BOOTSTRAP.md
.codex/bootstrap/PROJECT_BOOTSTRAP.md
.codex/bootstrap/AI_PROJECT_CONTEXT.md
ROADMAP.md
```

If the document concerns Laravel module architecture, implementation, refactor, rebuild, database, Livewire, or admin UI, also read as applicable:

```text
.codex/standards/MODULE_STANDARD.md
.codex/standards/ADMIN_UI_STANDARD.md
```

Use repository reality as the source of truth when generic/legacy guidance conflicts with the current project.

## 2. Input

Input must identify exactly one Markdown file.

Read the complete relevant document before rewriting it.

If the document references source files that are necessary to verify a material claim, inspect only those directly referenced files.

## 3. Analysis

Apply a senior software architect mindset.

Do more than summarize. Review:

- purpose and scope
- business requirements
- architecture
- data flow
- dependencies
- missing requirements
- contradictions
- unclear assumptions
- security risks
- data-integrity risks
- scalability/performance concerns
- maintainability concerns
- implementation ambiguity
- acceptance criteria
- compatibility with canonical project/module/UI standards when applicable

Label uncertain findings as:

```text
Evidence
Inference
Assumption
Unknown
```

Do not invent missing business rules.

## 4. Output

Create or update:

```text
docs/analysis/<document_name>_ANALYSIS.md
```

The result should contain:

```text
Executive Summary
Original Goal
Confirmed Requirements
Architecture Analysis
Standards Compatibility
Design Problems / Gaps
Security / Data Integrity Risks
Performance / Maintainability Risks
Missing Decisions
Recommended Improvements
Proposed Revised Structure
Codex-Ready Implementation Prompt
Open Questions / Needs Confirmation
```

When useful, rewrite the supplied document into a clearer structure inside the analysis output rather than modifying the original file.

## 5. Rules

- Documentation-only task.
- Do not modify application source code.
- Do not silently assume missing business logic.
- Do not overwrite the original input document unless explicitly requested.
- Keep recommendations concrete and implementation-ready.
- Mark unknowns and explain how to verify them.
- Be idempotent.

## Final Response

Return a short summary with:

- analyzed document
- generated/updated analysis file
- major gaps found
- unresolved questions, if any
- confirmation that application source code was not modified
