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
1. Verify fresh GitHub main and WordPress core version. Export/recover production core 0.2.3 via authorized current deployment channel; compare to baseline and retain every newer behavior.
2. Merge candidate V6 into 0.2.3; rerun npm test, npm run test:search, npm run test:queue, npm run test:local-ai, npm run test:v6, node tests/source-engine.mjs.
3. Prepare rollback ZIP of actual production code, then update only oberhub-core. Preserve live DB and complete add-only import in batches. Run all 5 mandatory live queries and record exact counts.
4. Configure available free AI provider credentials server-side; verify true inference and failover. Until then evidence-pack fallback is explicit, not claimed generative.
5. Expand corpus through scripts/harvest-v6-documents.py and 69 manifest seeds; review discovered links before ingestion. Do not count discovered links as verified forms.
6. Add semantic retrieval/reranker, wider ontology, geo distances/expansion, massive forms/tax/medical coverage and pre-generated synthesis packs. None of these larger targets is claimed complete.

## Workspace
Run from repository root. Candidate activity generator: python3 scripts/expand-v6-activities.py.
Harvester: python3 scripts/harvest-v6-documents.py --max-sources 12 --max-pages 24 --timeout 8.
No secrets, hosting configuration, personal queries or live database exported into public GitHub.

## Additive deployment alternative
Install only wp-content/plugins/oberhub-v6. Keeps core 0.2.3 files and its existing index. Then POST /oberhub/v1/v6/prepare, call /v6/import-batch until complete, POST /v6/build-index, GET /v6/status and verify mandatory live queries. Deactivation immediately restores original core routes. No theme publish required.
