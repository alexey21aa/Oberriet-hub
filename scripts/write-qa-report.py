#!/usr/bin/env python3
from pathlib import Path
import json
R=Path(__file__).resolve().parents[1]
checks=[]
for filename in ('unit.json','qa.json'):
 checks.extend(json.loads((R/'tests/results'/filename).read_text()))
passed=sum(c['passed'] is True for c in checks);failed=sum(c['passed'] is False for c in checks);skipped=sum(c['passed'] is None for c in checks)
assert failed==0, 'Cannot publish a passing report with failed checks'
text=f'''# QA report · 2026-10-01 · 0.2.0

**{passed} checks passed · {failed} failed · {skipped} skipped** across the main unit and WordPress runtime suites. Full evidence: `tests/results/unit.json` and `qa.json`.

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

GitHub returns account alexey21aa with zero repositories/installations and no repository-creation operation. WPVibe returns zero registered sites. No public deployment, paid hosting purchase or remote repository creation is claimed.

## Main check evidence

| Check | Result |
|---|---|
'''
for c in checks:
 label=c['name'].replace('|','/').replace('\n',' ')
 text+=f"| {label} | {'PASS' if c['passed'] is True else 'SKIP' if c['passed'] is None else 'FAIL'} |\n"
(R/'docs/QA_REPORT.md').write_text(text)
print(json.dumps({'passed':passed,'failed':failed,'skipped':skipped}))
