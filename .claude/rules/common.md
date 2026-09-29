# Common Conventions

Cross-cutting rules that apply to every file type, regardless of language.

- DO: **Trailing newline at EOF** — every text file (`.php`, `.html`, `.scss`, `.yml`, `.yaml`, `.json`, `.md`, `.sh`, `.neon`, etc.) ends with a single `\n`. POSIX expects it; PHP-CS-Fixer / Prettier / `git diff` / `cat` all assume it. Missing newlines surface as `\ No newline at end of file` in diffs.
- DO: **LF line endings** — never CRLF. Configure `git config --global core.autocrlf input` on macOS/Linux. The devcontainer's post-create already does this.
- DO: **UTF-8 encoding** — no BOM, no Latin-1. PHP files in particular: no BOM (it breaks header output).
- DO: **No tabs in PHP or YAML.** PHP: 4-space indent (PSR-12). YAML/JSON: 2-space indent. Markdown: 2-space for nested lists.
- DO: **One blank line between top-level declarations**, none at file start. A `<?php` line with `declare(strict_types=1);` immediately after, then a blank line, then `namespace ...;`.
- NEVER: **Trailing whitespace** on any line. PHP-CS-Fixer strips it for `.php`; other formats: configure editor or pre-commit hook.
