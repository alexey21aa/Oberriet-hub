# QA report · 2026-10-01

**88 checks passed · 0 failed · 2 skipped.**

Environment: WordPress 7.1.2, PHP 8.3 WASM, Playground SQLite integration, real Chromium 153 via Playwright 1.62.1. Production target: native PHP 8.3+ and MariaDB/MySQL on Swiss hosting. Machine-readable evidence is in `tests/results/qa.json`, `unit.json` and `accessibility.json`.

The nine mandatory search phrases all pass with the correct top intent: Wohnsitzbestätigung, wo anmelden, переезд, куда сообщить адрес, сміття Montlingen, garbage Kriessern, school registration, фонарь не работает, Steuererklärung. Unknown gibberish produces no fabricated answer. Date tests cover Swiss rollover, weekly routes, remaining paper dates, green-waste non-collection and refusal to reuse the 2026 plan in 2027. ICS has valid CRLF, UTF-8 folding and exclusive all-day end dates.

Runtime tests verify four languages, 23 core route types, 404, canonical/hreflang/sitemap presence, local JavaScript loading, single H1, concrete user journeys, German draft/copy-mailto behaviour, clearing and no resident names/addresses in HTTP traffic or browser storage. Monthly Montlingen calendar includes all four October refuse rounds, not just the next round.

Responsive widths: 360×800, 390×844, 768×1024, 1366×768, 1920×1080. All pass the no-horizontal-overflow check. Desktop/mobile screenshots were visually inspected. Keyboard skip navigation passes. axe WCAG 2 A/AA, 2.1 A/AA and 2.2 AA tag scans on DE home, RU ask and EN residence detail report zero violations. This is an automated sample audit, not full WCAG certification.

Security tests verify source URL/SSRF restrictions, invalid import rejection, denial for editor role, XML-RPC method removal, output escaping, public AI POST denial, anonymous dashboard redirect, actual retention deletion, counter increments, administrator TOTP challenge and successful login, authenticated export/import, and nonce-free settings POST denied with 403. TOTP uses a disposable 160-bit lab key and an independently calculated HMAC token matched to the PHP lab clock; no lab enrollment or clock endpoint is in the production distribution. Password alone does not enter the admin.

## Browser / server limits

| Required environment | Actual status |
|---|---|
| Chrome desktop | Chromium 153 automated checks passed |
| Chrome Android | Mobile viewport emulation passed; physical Android pending |
| Samsung Internet | Physical browser not available; pending |
| Safari iPhone / WebKit | WebKit engine unavailable, explicitly skipped |
| Firefox | Engine unavailable, explicitly skipped |
| Edge | Chromium compatibility exercised; actual Edge pending |
| Five required viewports | Passed |
| Real HTTPS / HTTP redirect | Config supplied; pending actual host |
| No directory listing / exposed .git / config | Apache/Nginx config supplied; pending actual host |
| Admin cache exclusion / native host cache | WordPress private admin functional; host rules pending |
| MariaDB/MySQL | Production target; lab used SQLite compatibility adapter |
| Backup restore | Instructions and script validated; actual host restore exercise pending |

No fabricated Lighthouse/PageSpeed score is supplied: WPVibe requires a public URL and none exists. No public production deployment or payment was performed.

## Detailed automated checks

| Check | Result |
|---|---|
| Search Wohnsitzbestätigung | PASS |
| Search wo anmelden | PASS |
| Search переезд | PASS |
| Search куда сообщить адрес | PASS |
| Search сміття Montlingen | PASS |
| Search garbage Kriessern | PASS |
| Search school registration | PASS |
| Search фонарь не работает | PASS |
| Search Steuererklärung | PASS |
| Unknown question returns no invented result | PASS |
| Unicode and Swiss orthography | PASS |
| Montlingen cardboard next Wednesday | PASS |
| Kriessern household waste Monday | PASS |
| Montlingen paper 7 November | PASS |
| Kobelwald mountain-area paper 2 November | PASS |
| No invented green collection date | PASS |
| 2026 waste data never reused for 2027 | PASS |
| Swiss local-date rollover | PASS |
| ICS UTF-8 folding <=75 octets | PASS |
| ICS inclusive event end exported exclusive | PASS |
| ICS mandatory delimiters and CRLF | PASS |
| Provenance references consistent | PASS |
| Four-language service summaries complete | PASS |
| PHP seed schema validation | PASS |
| Source SSRF rejection http://www.oberriet.ch/ | PASS |
| Source SSRF rejection https://127.0.0.1/ | PASS |
| Source SSRF rejection https://user:password@www.oberriet.ch/ | PASS |
| Source SSRF rejection https://www.oberriet.ch.evil.example/ | PASS |
| Source SSRF rejection https://www.oberriet.ch:8080/ | PASS |
| Invalid import rejected | PASS |
| Editors cannot manage Hub data | PASS |
| XML-RPC methods disabled | PASS |
| Output escaping | PASS |
| Two Factor plugin loaded | PASS |
| PHP syntax AdminDashboard.php | PASS |
| PHP syntax Analytics.php | PASS |
| PHP syntax Calendar.php | PASS |
| PHP syntax ContentTypes.php | PASS |
| PHP syntax Frontend.php | PASS |
| PHP syntax Search.php | PASS |
| PHP syntax Security.php | PASS |
| PHP syntax Sources.php | PASS |
| Technical/security/audit retention purge | PASS |
| Aggregate 13-month purge | PASS |
| Aggregate counter increments | PASS |
| TOTP is required for QA administrator | PASS |
| WordPress home 200 | PASS |
| Home renders Hub | PASS |
| Security headers | PASS |
| Seed imported | PASS |
| Language de | PASS |
| Language en | PASS |
| Language ru | PASS |
| Language uk | PASS |
| Unknown route 404 | PASS |
| Anonymous AI write denied | PASS |
| Sitemap all languages | PASS |
| All 23 core route types render | PASS |
| Anonymous dashboard redirects to login | PASS |
| JS loaded | PASS |
| One H1 | PASS |
| Residence search | PASS |
| German email draft | PASS |
| Mailto only | PASS |
| No resident fields in HTTP traffic | PASS |
| No resident storage | PASS |
| Clear draft | PASS |
| Montlingen waste | PASS |
| Responsive 360 | PASS |
| Responsive 390 | PASS |
| Responsive 768 | PASS |
| Responsive 1366 | PASS |
| Responsive 1920 | PASS |
| Monthly calendar includes all four October rounds | PASS |
| Locality page | PASS |
| Keyboard skip link | PASS |
| Skip lands on main | PASS |
| Accessibility axe /de/ | PASS |
| Accessibility axe /ru/ask/ | PASS |
| Accessibility axe /en/services/23627/ | PASS |
| No JavaScript errors | PASS |
| Password alone requires TOTP | PASS |
| TOTP login succeeds | PASS |
| Closed admin dashboard | PASS |
| Settings CSRF without nonce denied | PASS |
| Authenticated JSON export | PASS |
| Native admin JSON import without overwrite | PASS |
| No resident text in log table | PASS |
| Firefox smoke | SKIP |
| WebKit smoke | SKIP |
