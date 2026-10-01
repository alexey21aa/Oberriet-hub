# QA report · 2026-10-01 · 0.2.0

**112 checks passed · 0 failed · 2 skipped** across the main unit and WordPress runtime suites. Full evidence: `tests/results/unit.json` and `qa.json`.

Environment: WordPress 7.1.2, PHP 8.3 WASM, Playground SQLite integration, real Chromium 153 via Playwright 1.62.1. Production target is native PHP and MariaDB/MySQL on Swiss hosting; that environment has not been deployed.

The final corpus validates: 364 distinct records, 2548 source-backed navigation answers/paths, 5143 unique canonical intents (2595 single/curated +2548 explicit compound goals), 71344 aliases, 293 sources. All 5096 compound answer references resolve to the same service and verified source registry. The three 27,178,580-byte seed files match. This does not mean 5143 independently verified factual procedures. Many answers explicitly refer unknown personal conditions/fees/time limits to their original source.

Separate passing suites: indexed retrieval/Unicode/typo/transposition and 2000 synthetic records with pagination; compound references/counts; 15 resumable-import and metadata regressions; complete PHP dataset validation; native local-AI adapter fallback/session-destruction tests using mocks. An actual generative browser model is unavailable here, so generation quality/hardware are untested.

Runtime checks cover the full import and indexed PHP retrieval, four languages, route types and 404, sitemap/SEO, same-site POST search, local email draft and copy/mailto, clearing and no resident fields in network traffic or local/session storage. All nine mandatory search phrases pass. Waste/ICS checks cover date rollover, expiry after 2026, no unsupported collection dates, cardboard, UTF-8 folding and all-day end dates.

Responsive widths 360,390,768,1366,1920 pass horizontal-overflow checks. Desktop/mobile/admin screenshots are included. Keyboard skip navigation passes. axe WCAG 2/2.1/2.2 A/AA sample scans of DE home, RU ask and EN residence detail have zero violations. This is a sample automated audit, not independent WCAG certification.

Security checks cover allowlisted HTTPS/SSRF rejection, import validation, duplicate IDs and impossible dates, nested CSV round trips, capability/nonce denial, XML-RPC removal, escaping, retention deletion and counters, admin TOTP challenge and actual successful login, authenticated export/import. Test TOTP uses a disposable laboratory key; the production package contains no enrollment, live credentials or lab clock endpoint.

## Limits requiring a deployed site

| Environment/check | Status |
|---|---|
| Chromium desktop and five mobile/tablet/desktop viewports | Passed |
| Firefox / WebKit | Engine unavailable; explicitly skipped |
| Physical Android, Samsung Internet, iPhone Safari, Edge | Not exercised |
| Native MariaDB/MySQL | Not exercised; lab used SQLite adapter |
| Public HTTPS, redirect, cache, cron, server file protections | Config examples included; actual host pending |
| Live backup restore | Script/instructions included; actual host restore pending |
| Live Lighthouse/PageSpeed / WPVibe public URL | Unavailable because no registered site/public URL exists |
| Large-corpus production PHP time/memory sizing | Must be measured; final index is one request |

GitHub identifies account alexey21aa, but the connector returns zero accessible repositories/installations and exposes no repository-creation operation. This does not establish whether inaccessible private repositories exist. WPVibe returns zero registered sites. No public deployment, paid hosting purchase or remote repository creation is claimed.

## Main check evidence

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
| PHP syntax ImportQueue.php | PASS |
| PHP syntax Knowledge.php | PASS |
| PHP syntax Search.php | PASS |
| PHP syntax Security.php | PASS |
| PHP syntax Sources.php | PASS |
| Technical/security/audit retention purge | PASS |
| Aggregate 13-month purge | PASS |
| Aggregate counter increments | PASS |
| TOTP is required for QA administrator | PASS |
| Organization/answer/FAQ/guide admin types registered | PASS |
| Duplicate stable IDs rejected | PASS |
| Impossible calendar date rejected | PASS |
| Unknown provenance rejected | PASS |
| Invalid localized field rejected | PASS |
| Invalid collection shape rejected | PASS |
| CSV keeps all nested translations and provenance | PASS |
| CSV mismatched stable ID rejected | PASS |
| CSV unsupported collection rejected | PASS |
| CSV malformed JSON rejected | PASS |
| Bulk review hooks registered | PASS |
| Source explicit HTTPS policy rejects fragments-as-host | PASS |
| Indexed PHP search Wohnsitzbestätigung | PASS |
| Indexed PHP search переезл | PASS |
| Indexed PHP search фонарь не работает | PASS |
| Indexed PHP unknown query returns no invented cards | PASS |
| Indexed PHP Unicode normalization | PASS |
| Indexed PHP transposition | PASS |
| Public REST indexed search | PASS |
| Search rejects excessive query | PASS |
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
| POST search returns residence service | PASS |
| Sitemap all languages | PASS |
| Core route types render | PASS |
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
| Monthly calendar includes October waste and cardboard rounds | PASS |
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
| Partial admin import preserves expanded intents and aliases | PASS |
| No resident text in log table | PASS |
| Firefox smoke | SKIP |
| WebKit smoke | SKIP |
