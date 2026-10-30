# Fresh-history GitHub repository duplication

## Scope

`repo-duplica.sh` creates an **independent** repository from the Git-tracked contents of the source's **GitHub `main`** branch. The destination starts with one `Initial commit`, not the source history. No fork, source branches/tags, issues, PRs, Actions secrets, local untracked files, `.env`, or database content are transferred.

## Prerequisites

Run on a development machine (e.g. Admin), **not Production**. Install `bash`, `git`, `gh`, `tar`, and `find`; authenticate using `gh auth login` with permission to create repositories in your account. The command uses the authenticated GitHub username as the owner for **both** repositories.

## Usage

```bash
# No repository is created; validates source, destination and one-commit snapshot.
bash repo-duplica.sh laravel-shop laravel-inafo --dry-run

# Private by default; prompts for exact 'tungocvan/laravel-inafo' before creation.
bash repo-duplica.sh laravel-shop laravel-inafo

# Explicitly create public destination (only if the code has been reviewed for disclosure).
bash repo-duplica.sh laravel-shop laravel-inafo --public
```

The source's default branch must be `main`. Destination must not already exist. The script uses a private temporary directory, clones the remote `main` shallowly, exports **tracked** content with `git archive`, initializes a fresh Git repository, creates one commit and calls `gh repo create --source . --remote origin --push`. It verifies the remote `main` SHA and that the destination has one commit.

### Safety and limitations

- **Never run against Production**. No Docker, database, deployment, or source-repository mutation is performed.
- The script rejects common tracked secret filenames (`.env*`, private keys, `auth.json`), but this is **not a comprehensive secret scan**. Inspect the tracked snapshot for embedded tokens, customer information, hard-coded credentials and public assets before creating a new repository.
- Private is the default. `--public` is an explicit disclosure decision.
- A destination already present causes a safe abort; no force push, history rewrite or deletion is performed.
- If GitHub creation succeeds but push or verification fails, the destination may remain partially created. **The script never automatically deletes it.** Inspect it manually before retrying.
- Temporary clones are cleaned automatically; the script does not remove existing files from the working repository.
- After duplication, review project-specific URLs, branding, README, CI secrets, remote environment configuration and licensing before deploying the new project.

## Verification gates

```bash
# Focused (syntax + automated contract)
bash -n repo-duplica.sh && node --test tests/Tooling/repo-duplica.test.cjs

# Tooling regression (only after focused PASS)
git diff --check && git status --short --branch
```

For an optional live test, run `--dry-run` first. **Do not run the actual creation command as part of automated tests**; it changes GitHub account state and requires a separate operator decision.

## Handoff

- Branch: `feat/repo-duplica-fresh-history-20261030`
- Source base: `main` `582191875828384d599ba2bd353ba0c4d63c357c`
- Focused test: PENDING user checkpoint
- Tooling regression: PENDING user checkpoint
- UI/module regression: NOT APPLICABLE (standalone CLI tooling)
- GitHub destination creation: NOT EXECUTED / requires explicit command and confirmation
- Merge/deploy: NOT AUTHORIZED
