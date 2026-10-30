# Fresh-history GitHub repository duplication (V2)

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

The source's default branch must be `main`. Destination must not already exist. The script uses a private temporary directory, clones the remote `main` shallowly, exports **tracked** content with `git archive`, initializes a fresh Git repository, creates one commit and creates the destination with `gh repo create`, then pushes `main` over HTTPS using scoped `gh auth git-credential` authentication. This avoids the SSH identity mismatch seen during the `laravel-inafo` creation and does not change global Git settings. It verifies the remote `main` SHA and that the destination has one commit.

### Safety and limitations

- **Never run against Production**. No Docker, database, deployment, or source-repository mutation is performed.
- The script rejects common tracked secret filenames (`.env`, `.env.*` except conventional `.env.example` / `.env.*.example` templates, private keys, `auth.json`), but this is **not a comprehensive secret scan**. Example templates remain included and their contents must also be reviewed. Inspect the tracked snapshot for embedded tokens, customer information, hard-coded credentials and public assets before creating a new repository.
- Private is the default. `--public` is an explicit disclosure decision.
- A destination already present causes a safe abort; no force push, history rewrite or deletion is performed.
- If GitHub creation succeeds but push or verification fails, the destination may remain partially created. V2 deliberately stops and does not automatically retry into an existing repository; inspect its actual refs and recover manually after review. **The script never automatically deletes it.** Inspect it manually before retrying.
- Temporary clones are cleaned automatically; the script does not remove existing files from the working repository.
- After duplication, review project-specific URLs, branding, README, CI secrets, remote environment configuration and licensing before deploying the new project.

## V3 interactive menu

Run `bash repo-duplica.sh` without arguments to open the menu. Existing two-argument V2 CLI commands remain supported.

1. **Check connection:** `gh auth status`, current API account and Git protocol.
2. **Duplicate:** prompt for source/destination, Dry Run by default, Private by default, then invoke the V2 HTTPS duplication implementation. Currently source and destination must be owned by the active GitHub account.
3. **DELETE a real GitHub repository:** requires an exact `owner/repository`, successful metadata inspection, admin permission, typing the exact repository name, then typing `DELETE PERMANENTLY`. Calls `gh api -X DELETE repos/owner/repository` only after all gates. GitHub may additionally require `delete_repo` OAuth scope; the tool does **not** elevate permissions automatically. **This is irreversible for the purposes of this tool.** It deletes the actual GitHub repository, not a local folder.
4. **Account:** view status, login and switch accounts via interactive GitHub CLI. Never display or store tokens in this script.

No bulk deletion, no `--yes`, no automatic deletion after push failure. **Do not test deletion against a real repository.** Run on an Admin/development machine, never Production.

### V3 checkpoint

```bash
bash -n repo-duplica.sh
bash -n scripts/repo-duplica-menu.sh
node --test tests/Tooling/repo-duplica.test.cjs
printf '0\\n' | bash repo-duplica.sh
bash repo-duplica.sh laravel-inafo laravel-demo --dry-run
git diff --check
git status --short --branch
```

V3 is built on the V2 feature branch and must not merge independently before its base is merged or reviewed. The tests are pending user execution; no real repository deletion or creation has been run for V3.

## Verification gates

```bash
# Focused (syntax + automated contract)
bash -n repo-duplica.sh && node --test tests/Tooling/repo-duplica.test.cjs

# Tooling regression (only after focused PASS)
git diff --check && git status --short --branch
```

For an optional live test, run `--dry-run` first. **Do not run the actual creation command as part of automated tests**; it changes GitHub account state and requires a separate operator decision.

## Handoff

- V1 merged in PR #352; `tungocvan/laravel-inafo` recovered successfully over HTTPS with one initial commit.
- V2 branch: `fix/repo-duplica-v2-https-20261030`
- Source base: latest GitHub `main` at branch creation.
- Focused test: V2 PENDING user checkpoint
- Tooling regression: V2 PENDING user checkpoint
- UI/module regression: NOT APPLICABLE (standalone CLI tooling)
- V2 destination creation: NOT EXECUTED / requires explicit command and confirmation
- Merge/deploy: NOT AUTHORIZED
