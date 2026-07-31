# Contributing to QuickTrend

First off — **thank you** for taking the time to contribute! 🎉
Every contribution matters, whether it is a typo fix, a bug report, a translation, or a new feature.

This project is maintained by a student and is a great place to make your **first open-source contribution**. If anything here is unclear, please open a [Discussion](../../discussions) or a draft PR and ask — questions are always welcome.

## Ways to contribute

- 🐛 **Report a bug** → [open a bug report](../../issues/new?template=bug_report.yml)
- 💡 **Suggest a feature** → [open a feature request](../../issues/new?template=feature_request.yml)
- 📝 **Improve docs** (README, comments, this file) — small PRs are very welcome
- 🌍 **Translate** UI strings toward i18n
- 👩‍💻 **Pick up code** → look for the [`good first issue`](../../issues?q=is%3Aissue+is%3Aopen+label%3A%22good+first+issue%22) and [`help wanted`](../../issues?q=is%3Aissue+is%3Aopen+label%3A%22help+wanted%22) labels

## Local setup

See the [Getting Started](README.md#getting-started-local-development) section of the README. In short:

```bash
git clone https://github.com/gfhajkafjhdfk/QuickTrend-of-backup.git
cd QuickTrend-of-backup
mysql -u root -p < database.sql
cp php/config.local.php.example php/config.local.php   # DB credentials
cp js/config.example.js js/config.local.js             # your own Mapbox pk. token (domain-restricted)
php -S localhost:8000
```

> Never commit secrets. `php/config.local.php` and `js/config.local.js` are git-ignored on purpose.

## Workflow

1. **Fork** the repository and create a branch from `main`:
   ```bash
   git checkout -b fix/short-description      # or feat/… , docs/… , chore/…
   ```
2. Make your change. Keep it focused — one logical change per PR.
3. **Test locally.** For PHP files, make sure they lint:
   ```bash
   php -l path/to/file.php
   ```
   The CI runs the same validation (`.github/scripts/validate.py`) before deploy, so a clean lint helps your PR pass.
4. **Commit** using clear, conventional-style messages:
   - `feat: add day/night toggle to map`
   - `fix: prevent duplicate vote within cooldown`
   - `docs: clarify Mapbox token setup`
5. **Open a Pull Request** against `main`. Fill in the PR template and link the issue it closes (`Closes #123`).
6. A maintainer will review. Please be patient and responsive to feedback — small back-and-forth is normal and healthy.

## Coding guidelines

- **PHP**: follow the existing style in `php/`. Prepared statements for all SQL, escape output, keep auth/CSRF/rate-limit patterns intact.
- **JavaScript**: match the style of the surrounding `js/` modules; avoid adding heavy dependencies without discussion.
- **Security**: never hardcode tokens, passwords, or IPs. Client tokens (e.g. Mapbox `pk.`) must be loaded from `js/config.local.js` and domain-restricted.
- Keep unrelated reformatting out of feature PRs.

## Reporting security issues

Please **do not** file security problems as public issues. Follow [SECURITY.md](SECURITY.md).

## Code of Conduct

By participating, you agree to uphold our [Code of Conduct](CODE_OF_CONDUCT.md).

---

Not sure where to start? Comment on any [`good first issue`](../../issues?q=is%3Aissue+is%3Aopen+label%3A%22good+first+issue%22) and we'll help you get going. 🙌
