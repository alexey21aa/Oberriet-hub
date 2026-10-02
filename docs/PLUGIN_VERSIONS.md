# Components

| Component | Version | Role |
|---|---|---|
| WordPress | 7.1.2 | Production core, current stable verified at wordpress.org |
| OberHub Core | 0.2.0 | Records, local search, imports, source checks, privacy |
| OberHub Theme | 0.2.0 | Original block theme and multilingual shell |
| Two Factor | 0.17.0 | TOTP/recovery codes |

The previous Fuse.js asset and MIT licence remain available in source for compatibility, but the current app uses the project index engine. WordPress Playground's SQLite adapter is development-only, not production. Node modules and browser binaries are excluded from production. No paid plugin or external API is mandatory.

Development: WP Playground CLI 3.1.56, Playwright 1.62.1, Chromium 153.0.0, axe-core 4.13.0. Official upstream: https://wordpress.org/news/2026/09/wordpress-7-1-2-release/ and https://wordpress.org/plugins/two-factor/.
