'use strict';

const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const root = path.resolve(__dirname, '../..');
const config = JSON.parse(fs.readFileSync(path.join(root, 'repomix.config.json'), 'utf8'));
const gitignore = fs.readFileSync(path.join(root, '.gitignore'), 'utf8');
const patterns = config.ignore.customPatterns;

test('Repomix keeps security scanning enabled and uses XML output', () => {
  assert.equal(config.security.enableSecurityCheck, true);
  assert.equal(config.output.style, 'xml');
  assert.equal(config.output.filePath, 'repomix-output.xml');
});

test('Repomix excludes private and generated content', () => {
  for (const pattern of [
    '**/.env.*', '**/*.pem', '**/*.key', '**/auth.json',
    '**/storage/app/**', '**/storage/logs/**',
    '**/vendor/**', '**/node_modules/**', '**/repomix-output*',
  ]) {
    assert.ok(patterns.includes(pattern), `Missing ignore pattern: ${pattern}`);
  }
});

test('Generated Repomix packs are Git-ignored without ignoring config', () => {
  assert.match(gitignore, /^\/repomix-output\*$/m);
  assert.match(gitignore, /^\/repomix-\*\.xml$/m);
  assert.doesNotMatch(gitignore, /^\/repomix\.config\.json$/m);
});
