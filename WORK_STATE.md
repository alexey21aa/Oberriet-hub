# Oberriet Hub continuation checkpoint — 2026-10-02

## Authority and production
V6 attached by owner supersedes all earlier specs. Continue existing project.
Production https://oberriethub.ch, WP 7.1.2, PHP 8.4.26, theme 0.2.0.
IMPORTANT latest WPVibe read at resumed session: core **0.2.3**, while restored Git baseline is 0.2.1. Production changed during pause. Do not install this candidate over production until current 0.2.3 code is exported and reconciled.
Latest live index: 4503 records, 5294 terms, indexed 2026-10-02T10:31:35Z.
WPVibe reads verified. DISALLOW_FILE_EDIT blocks draft editing. DISALLOW_FILE_MODS not defined. No production code or data changed at this checkpoint. An additive oberhub-v6 extension is now prepared to preserve live core 0.2.3 while adding its own index and routes; it stays dormant until authenticated preparation completes.

## Completed
- Imported complete native Git history through 37114bd with GitHub Actions run 37033235836 (success). Main import commit f8fb75015b2ddf70aa582e04b1b4fa16c9f8cd6f. Source ZIP verified byte-for-byte against bundle; archives and original GitHub history retained.
- Candidate core 0.3.0: shared Unicode-normalized multilingual concept dictionary, one-edit aliases, corrected доя, server audience/activity filters and locality tiers. No administrative results for curated activity intents.
- Added 20 real primary-provider activities, 11 primary source pages (HTTP 200 and body SHA256). TSV old URL 404 discovered and corrected to new /riegen pages.
- Server AI gateway uses full server retrieval; ignores client context/device. Two attempts, provider cooldown, conservative activity cache with HMAC keys, citation/amount validation, rate limit, no paid providers. Arbitrary personal queries not cached; detected PII/personal medical questions not sent externally.
- Browser primary search no longer silently substitutes reduced local corpus on server failure. Local inference remains optional.
- Add-only authenticated activity-import endpoint prepared; not invoked live.
- Document metadata harvester prepared with robots, conditional GET, bounds, dedupe. 69 source seeds from V6. Initial smoke run: 2 fetched pages, 1 discovered link, 0 verified forms. A larger run was interrupted during conversation pause; not claimed completed.

## Tests and scope
531 activity/typo/location regression cases passed locally. 53 PHP server/ranking/syntax/grounding checks passed. Existing unit/search/queue/local-AI checks passed. Source-engine fixture updated to include QueryUnderstanding; rerun completed source suite on resume.
These are candidate tests, not live acceptance. No external provider inference tested and no API credentials configured. Filters currently depend on activity tags; broader ontology tagging is next.

## Exact next work
1. Install the prepared isolated addon through wp-admin ZIP upload. User explicitly authorized browser fallback and installation. Preserve live core 0.2.3. Browser credentials and second factor were accepted, dashboard verified, but the browser runtime then blocked file selection with native credential protection. ZIP upload did not complete. Do not bypass that protection or repeat connector authorization.
2. After addon activation, use WPVibe POST /oberhub/v1/v6/prepare, then /v6/import-batch until complete, then /v6/build-index. Verify /v6/status and all five live queries. Current baseline failures recorded in tests/results/v6-live-baseline.json.
3. Export/reconcile current core 0.2.3 only when a supported channel allows it; candidate core files are not the live production version. Integrate frontend server answer UI without replacing newer live code blindly.
4. Configure available free AI provider credentials server-side; verify true inference and failover. Until then evidence-pack fallback is explicit, not claimed generative.
5. Expand corpus through scripts/harvest-v6-documents.py and 69 manifest seeds; review discovered links before ingestion. Do not count discovered links as verified forms.
6. Add semantic retrieval/reranker, wider ontology, geo distances/expansion, massive forms/tax/medical coverage and pre-generated synthesis packs. None of these larger targets is claimed complete.

## Workspace
Run from repository root. Candidate activity generator: python3 scripts/expand-v6-activities.py.
Harvester: python3 scripts/harvest-v6-documents.py --max-sources 12 --max-pages 24 --timeout 8.
No secrets, hosting configuration, personal queries or live database exported into public GitHub.

## Additive deployment alternative
Install only wp-content/plugins/oberhub-v6. Keeps core 0.2.3 files and its existing index. Then POST /oberhub/v1/v6/prepare, call /v6/import-batch until complete, POST /v6/build-index, GET /v6/status and verify mandatory live queries. Deactivation immediately restores original core routes. No theme publish required.

## Deployment tool result on final resumed attempt
- Candidate V6 saved in main commit 558b9c56ff7e820d687f728494cb8775657229ee.
- Isolated addon and ZIP saved in main commit 417fcc185866e52955e5ef0fd4fd5b84745d9c7f.
- 531 query regressions, 60 PHP checks (including addon syntax), 26 source checks passed.
- WPVibe plugin install of pinned raw GitHub ZIP returned `Plugin not found` (emulator treats URL as WordPress.org slug). No plugin was installed/activated. Core 0.2.3 remains live.
- Available tools cannot read/write core plugin files or upload custom plugin ZIP. Theme editing blocked by DISALLOW_FILE_EDIT; do not weaken it. Next deployment path is standard wp-admin plugin ZIP upload, using browser only after approval for connector fallback, or owner's upload.
- Package: releases/oberhub-v6-0.1.0.zip. 17221 bytes; blob a4c1380433f417efbcefacf23b2ddc5c01f957f6. Installing addon alone does not override live routes until /v6/build-index completes.
- No external generative inference configured or claimed. New data still candidate only.

## Latest authorized continuation
- Browser fallback and addon installation approved explicitly by owner. WP admin sign-in and second factor completed; dashboard displayed alexey21aa. File chooser operation failed before ZIP upload with native credential observation protection; runtime reset did not recover. No plugin installation or activation occurred, confirmed again by WPVibe plugin list.
- Fresh live core remains 0.2.3. All five mandatory live queries FAIL: first query returns administrative early childhood support; the other four return zero hits. Candidate passes 531 queries and 60 PHP checks again. Do not confuse local tests with live acceptance.
- Harvester now saves atomic per-page progress and supports --source-offset for additional source batches; broadened discovery to social, education, sport, children and integration pages. Discoveries remain unreviewed and never count as verified forms.
- Runtime login protection is a deployment blocker, not an approval refusal or a WordPress permission failure. Production data remains unchanged.
- Completed next harvest batch: source offset 12, 7 attempted pages, 1 HTTP 200 page, 25 discovered links before removing two self-navigation links; total stored unique discoveries 24, verified forms 0. Curated activity delta remains 20 services / 11 verified source pages; live index remains 4503 records. Next harvest source offset 18.
