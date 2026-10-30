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

test('Rejects missing arguments and equal repository names before network calls', () => {
  for (const args of [[], ['laravel-shop', 'laravel-shop'], ['../unsafe', 'laravel-inafo']]) {
    const result = spawnSync('bash', [script, ...args], { encoding: 'utf8' });
    assert.notEqual(result.status, 0, args.join(' '));
  }
});

test('Requires explicit confirmation and defaults to private', () => {
  assert.match(source, /visibility=--private/);
  assert.match(source, /--public\) visibility=--public/);
  assert.match(source, /\[\[ "\$confirmation" == "\$dst" \]\]/);
  assert.match(source, /if \[\[ "\$dry_run" == true \]\]; then/);
});

test('Copies only Git-tracked remote main into fresh history and checks destination', () => {
  assert.match(source, /git clone --quiet --depth 1 --single-branch/);
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
