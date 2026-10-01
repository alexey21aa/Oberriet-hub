# Installed components

| Component | Version | License / purpose |
|---|---|---|
| WordPress | 7.1.2 | GPLv2+, current stable verified 2026-10-01 |
| OberHub Core | 0.1.0 | GPL-2.0-or-later, project plugin |
| OberHub Theme | 0.1.0 | GPL-2.0-or-later, project block theme |
| Two Factor | 0.17.0 | GPLv2+, TOTP and recovery codes; official metadata tested up to WP 7.1.2 |
| Fuse.js | 7.5.0 | MIT, local fuzzy search, license bundled |

Only OberHub Core and Two Factor are required active plugins. Playground's SQLite integration is a **test-runtime component** and is not installed in the MariaDB production package. Default WordPress Hello Dolly/Akismet are removed from the new-site distribution because they are unnecessary. No paid cache, translation, SEO, form or AI plugin is required. Native host cache is optional after testing.

Development-only: WP Playground CLI 3.1.56, Playwright 1.62.1, Sparticuz Chromium 153.0.0 and axe-core tooling; not shipped under production web-root. Browser test binaries and node_modules are excluded from the deliverable.

Official upstream links:

- https://wordpress.org/download/releases/
- https://wordpress.org/plugins/two-factor/
- https://downloads.wordpress.org/plugin/two-factor.0.17.0.zip
- https://github.com/krisk/Fuse/blob/main/LICENSE
