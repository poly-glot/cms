#!/usr/bin/env node
import { spawnSync } from 'node:child_process';
import path from 'node:path';
import fs from 'node:fs';

const WORKSPACE_ROOT = path.resolve(import.meta.dirname, '../..');
const PHP_CS_FIXER = path.join(WORKSPACE_ROOT, 'vendor/bin/php-cs-fixer');
const PRETTIER = path.join(WORKSPACE_ROOT, 'node_modules/.bin/prettier');
const STYLELINT = path.join(WORKSPACE_ROOT, 'node_modules/.bin/stylelint');
const FRONTEND_ROOTS = ['webroot/js', 'webroot/css'].map((dir) => path.join(WORKSPACE_ROOT, dir) + path.sep);

const phpCsFixer = (file) => [[PHP_CS_FIXER, ['fix', '--quiet', '--using-cache=no', '--', file], [0, 8]]];
const prettier = (file) => [[PRETTIER, ['--write', '--log-level', 'silent', file], [0]]];
const stylelintThenPrettier = (file) => [[STYLELINT, ['--fix', '--quiet', file], [0, 2]], ...prettier(file)];

const FORMATTERS = Object.freeze({
  '.css': stylelintThenPrettier,
  '.js': prettier,
  '.mjs': prettier,
  '.php': phpCsFixer,
});

async function readStdin() {
  let raw = '';
  for await (const chunk of process.stdin) raw += chunk;
  return raw;
}

function parseInput(raw) {
  try {
    return JSON.parse(raw || '{}');
  } catch {
    return {};
  }
}

const raw = await readStdin();
const filePathInput = parseInput(raw).tool_input?.file_path;

if (typeof filePathInput !== 'string' || filePathInput.length === 0 || filePathInput.includes('\0')) {
  process.exit(0);
}

const resolvedPath = path.resolve(filePathInput);
if (!resolvedPath.startsWith(WORKSPACE_ROOT + path.sep)) {
  process.exit(0);
}

const extension = path.extname(resolvedPath);
const isFrontendFile = FRONTEND_ROOTS.some((root) => resolvedPath.startsWith(root));
const formatter = FORMATTERS[extension];
if (formatter === undefined || (extension !== '.php' && !isFrontendFile)) {
  process.exit(0);
}

for (const [bin, args, successCodes] of formatter(resolvedPath)) {
  if (!fs.existsSync(bin)) {
    console.log(`[auto-format] ${path.basename(bin)} not installed yet; skipping ${resolvedPath}.`);
    process.exit(0);
  }

  const result = spawnSync(bin, args, { stdio: 'ignore', shell: false, timeout: 30000 });
  if (!successCodes.includes(result.status)) {
    console.log(`[auto-format] ${path.basename(bin)} exited ${result.status} on ${resolvedPath}.`);
    process.exit(0);
  }
}

console.log(`Auto-formatted ${resolvedPath}.`);
process.exit(0);
