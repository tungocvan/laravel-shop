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
