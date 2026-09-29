#!/usr/bin/env node
import { spawnSync } from 'node:child_process';
import path from 'node:path';
import fs from 'node:fs';

const WORKSPACE_ROOT = path.resolve(import.meta.dirname, '../..');
const BIN_DIR = path.join(WORKSPACE_ROOT, 'vendor/bin');
const ALLOWED_EXTENSIONS = Object.freeze(['.php']);

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
if (!ALLOWED_EXTENSIONS.includes(path.extname(resolvedPath))) {
  process.exit(0);
}

const phpstanBin = path.join(BIN_DIR, 'phpstan');
if (!phpstanBin.startsWith(BIN_DIR + path.sep) || !fs.existsSync(phpstanBin)) {
  console.log(`[phpstan] phpstan not installed yet; skipping ${resolvedPath}.`);
  process.exit(0);
}

const result = spawnSync(
  phpstanBin,
  ['analyse', '--no-progress', '--error-format=raw', '--memory-limit=512M', '--', resolvedPath],
  { encoding: 'utf8', shell: false, timeout: 30000 }
);

if (result.stdout?.trim()) {
  console.log(`[phpstan] ${result.stdout.trim()}`);
}

process.exit(0);
