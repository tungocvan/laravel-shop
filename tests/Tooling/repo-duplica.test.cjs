'use strict';
const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { spawnSync } = require('node:child_process');

const script = path.resolve(__dirname, '../../repo-duplica.sh');
const source = fs.readFileSync(script, 'utf8');

test('Bash syntax is valid', () => {
  const result = spawnSync('bash', ['-n', script], { encoding: 'utf8' });
  assert.equal(result.status, 0, result.stderr);
});

test('Rejects invalid or equal repository names before network calls', () => {
  for (const args of [['laravel-shop', 'laravel-shop'], ['../unsafe', 'laravel-inafo'], ['laravel-shop']]) {
    const result = spawnSync('bash', [script, ...args], { encoding: 'utf8' });
    assert.notEqual(result.status, 0, args.join(' '));
  }
});

test('Requires explicit confirmation and defaults to private', () => {
  assert.match(source, /visibility=--private/);
  assert.match(source, /--public\) visibility=--public/);
  assert.match(source, /\[\[ "\$confirmation" == "\$dst" \]\]/);
  assert.ok(source.includes('if [[ $# -eq 0 ]]; then'));
});

test('Copies only Git-tracked remote main into fresh history and checks destination', () => {
  assert.match(source, /clone --quiet --depth 1 --single-branch/);
  assert.ok(source.includes('credential.helper="!gh auth git-credential"'));
  assert.match(source, /git -C "\$tmpdir\/source" archive HEAD/);
  assert.match(source, /git -C "\$tmpdir\/new" init --quiet -b main/);
  assert.match(source, /rev-list --count HEAD/);
  assert.match(source, /remote_sha/);
  assert.match(source, /remote_count/);
  assert.doesNotMatch(source, /git push --force/);
});

test('Allows conventional env example templates without allowing actual env files', () => {
  assert.ok(source.includes("! -name '.env.example'"));
  assert.ok(source.includes("! -name '.env.*.example'"));
  assert.ok(source.includes("-name '.env' -o -name '.env.*'"));
  assert.ok(source.includes("-name '*.pem'"));
  assert.ok(source.includes("-name 'auth.json'"));
});

test('V2 uses scoped GitHub CLI HTTPS credentials without SSH or global changes', () => {
  assert.ok(source.includes('gh repo create "$dst" "$visibility"'));
  assert.ok(source.includes('remote add origin "https://github.com/$dst.git"'));
  assert.ok(source.includes('credential.helper="!gh auth git-credential"'));
  assert.ok(source.includes('push -u origin main'));
  assert.doesNotMatch(source, /gh repo create[^\\n]*--push/);
  assert.doesNotMatch(source, /gh auth setup-git/);
  assert.doesNotMatch(source, /git push --force|push --force/);
});

test('V2 does not automatically delete or overwrite partially created destinations', () => {
  assert.ok(source.includes('Destination already exists: $dst'));
  assert.ok(source.includes('Do not rerun or delete it'));
  assert.ok(source.includes('remote_count'));
});

const menuScript = path.resolve(__dirname, '../../scripts/repo-duplica-menu.sh');
const menuSource = fs.readFileSync(menuScript, 'utf8');

test('V3 menu syntax and no-argument entrypoint', () => {
  const result = spawnSync('bash', ['-n', menuScript], { encoding: 'utf8' });
  assert.equal(result.status, 0, result.stderr);
  assert.ok(source.includes('if [[ $# -eq 0 ]]; then'));
  assert.ok(source.includes('scripts/repo-duplica-menu.sh'));
  const exit = spawnSync('bash', [script], { input: String.fromCharCode(48, 10), encoding: 'utf8' });
  assert.equal(exit.status, 0, exit.stderr);
  assert.match(exit.stdout, /REPO DUPLICA V3/);
});

test('V3 requires two explicit exact confirmations for destructive GitHub deletion', () => {
  assert.ok(menuSource.includes('valid_full_repo "$target"'));
  assert.ok(menuSource.includes('permissions.admin'));
  assert.ok(menuSource.includes('[[ "$confirm" == "$target" ]]'));
  assert.ok(menuSource.includes('DELETE PERMANENTLY'));
  assert.ok(menuSource.includes('gh api -X DELETE "repos/$target"'));
  assert.doesNotMatch(menuSource, /gh repo delete|--yes|delete_repo.*-s/);
});

test('V3 preserves CLI compatibility and never deletes on duplication failure', () => {
  assert.ok(menuSource.includes('bash "$ROOT_DIR/repo-duplica.sh"'));
  assert.ok(menuSource.includes('args+=(--dry-run)'));
  assert.ok(menuSource.includes('gh auth login --hostname github.com'));
  assert.ok(menuSource.includes('gh auth switch --hostname github.com'));
  assert.doesNotMatch(source, /gh api -X DELETE/);
});

test('V3 accepts exactly one owner/name separator for duplicate and delete inputs', () => {
  const validators = menuSource
    .replace(/^ROOT_DIR=.*$/m, 'ROOT_DIR=/tmp')
    .replace(/^main .*$/m, '');
  for (const [name, valid] of [
    ['tungocvan/laravel-inafo', true],
    ['vhdtshop-ux/laravel-demo', true],
    ['tungocvan/laravel-demo', true],
    ['tungocvan/repo/extra', false],
    ['/laravel-inafo', false],
    ['tungocvan/', false],
    ['tungocvan//repo', false],
    ['../unsafe', false],
    ['laravel-inafo', false],
  ]) {
    const result = spawnSync('bash', ['-c', validators + String.fromCharCode(10) + 'valid_full_repo "$1"', 'validator', name], { encoding: 'utf8' });
    assert.equal(result.status === 0, valid, name + ': ' + result.stderr);
  }
});
