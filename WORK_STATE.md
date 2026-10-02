# Oberriet Hub continuation checkpoint — 2026-10-02

## Authority and production
V6 attached by owner supersedes all earlier specs. Continue existing project.
Production https://oberriethub.ch, WP 7.1.2, PHP 8.4.26, theme 0.2.0.
IMPORTANT latest WPVibe read at resumed session: core **0.2.3**, while restored Git baseline is 0.2.1. Production changed during pause. Do not install this candidate over production until current 0.2.3 code is exported and reconciled.
Latest live V6 index: 4523 records, 5442 terms, indexed 2026-10-02T21:06:37Z. Active core 0.2.3 + addon 0.1.0.
WPVibe reads verified. DISALLOW_FILE_EDIT blocks draft editing. DISALLOW_FILE_MODS not defined. Owner installed and activated isolated addon 0.1.0. WPVibe import completed 31/31 rows (20 services, 11 sources), zero skips. Its separate V6 index is ready and live routes now use v6-server-index. Existing core 0.2.3 preserved.

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
1. All five mandatory live queries now PASS. Preserve active core 0.2.3 and addon 0.1.0. Do not repeat bundled import. Evidence: tests/results/v6-live-acceptance.json.
2. Latest candidate addon 0.1.3 adds 12 prebuilt multilingual answer packs and authenticated add-only dataset batches via POST /v6/prepare {dataset: ...}; validate first, then import batches, then explicit /v6/build-index. No raw SQL, overwrite, or external automatic host approval. Not deployed yet. Package release/oberhub-v6-0.1.3.zip; source and package saved in GitHub.
3. Review and verify content of 232 discovered document links. Convert relevant official forms/primary providers to multilingual factual records with evidence. Discovery metadata is NOT verified content and has NOT been imported live. Next harvester source offset 36. Current batch offset18: 36 attempted pages, 12 HTTP200, 208 discoveries.
4. Configure free server AI credentials through supported secure operator channel. Live answer is evidence-pack, not generative. Test inference/failover and surface server answer in current frontend after recovering newer core assets safely.
5. Add index change tracking/rebuild scheduling, semantic reranker, wider ontology, geo distances, massive medicine/tax/forms coverage. These remain pending.

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

## Live acceptance — 2026-10-02 21:06 UTC
Owner installed addon; authenticated status initially ready=false. Fixed initialization through WPVibe: prepare, four import-batch calls, build-index. Imported 31/31 without overwrite. Search results: sport children and typo 13 each; dance women and typo 3 each; indoor entertainment children 1 (Tanoshii). No administrative services in returned activity results. Device low/high and injected client context produce identical seven server-selected records and sources; no provider inference configured. Core 0.2.3 untouched.
Candidate 0.1.1: 12 packs (3 canonical intents ×4 languages), safe aliases include all five queries. Runtime rejects changed/missing facts and extra age/place/fee constraints. 48 pack checks and 11 import boundary checks pass; 531 search checks and 61 PHP checks pass. No automatic claim of generative output; packs mode curated-answer-pack. Candidate changes not live yet.
Historical deployment blockers and failures below earlier checkpoints were resolved by owner's addon installation; retain as history, not current state.

## Autonomous continuation — 2026-10-02 21:35 UTC
Owner explicitly requests continuous progress; no additional routine permission prompts. New candidate addon **0.1.2** bundles a reviewed life-services batch (13 services / 11 sources), plus 12 editorial answer packs and authenticated external add-only import. Install the single latest package instead of older 0.1.1. Live is still 0.1.0 and passing the five mandatory queries; no candidate changes here are claimed deployed.
- Imported live so far: 20 activity services +11 sources; 4523 index records /5442 terms.
- Research corpus: 232 discovered links (not verified forms); new 13 life services /11 sources are candidate. Topics: free advice/forms/letters, women/men meetings, family literacy, German courses, KulturLegi, insurance premium subsidy, tax forms/deadline extension, bridge benefits. No staff personal contacts republished.
- Direct receipts: 2/11 HTTP200 hashes, 9 fetch attempts timed out. Primary web content was reviewed; failed direct fetches marked fetch_status=error, so freshness prevents inclusion in external AI context. Do not describe all11 as direct HTTP verified. Scripts: expand-v6-life-services.py, then verify-v6-life-pages.py. Remaining receipt failures need revalidation.
- Ranking fixed beyond activity intents: removed blanket municipal host boost; prefer complete intent/title coverage and reviewed page evidence, keep exact intent aliases strong; locality terms handled separately. Navigation answers from the same service are collapsed before pagination. JS and PHP implementations match.
- New bounded index refresh hooks: record edits/adds/deletes mark revision and debounce a WP-Cron rebuild; core active imports defer refresh; newer edits during rebuild remain dirty. WP-Cron depends on traffic. Explicit /v6/build-index remains available. Live scheduling acceptance after deployment remains pending.
- Tests PASS: original search suite (including 2000 records/pagination), 531 activity queries, 31 life queries, 67 PHP checks, 48 answer-pack checks, 11 import-boundary checks. No free external provider inference configured or tested.
### Exact next live steps
1. Deploy latest isolated addon 0.1.2 through supported plugin ZIP upload; core0.2.3 untouched. WPVibe install emulator supports registry slugs only; browser native-credential protection previously blocked upload. Do not repeat a known failing URL install or weaken security.
2. POST /oberhub/v1/v6/prepare with {"batch":"life"}; call import-batch through complete; POST build-index. Should add13 search records (sources not indexed) without overwrite. Verify all five old queries + tests/results/v6-life-search.json examples live. Preserve old data.
3. Check /v6/status dirty flag after a reversible test record edit, verify scheduled refresh; no broad repeated index rebuilds.
4. Further reviewed data can use prepare {"dataset": ...}, maximum500 rows/1MB, existing approved hosts, no overwrite. Metadata-only unreviewed links must stay out of published curated records.
5. Continue direct source revalidation, SG/Rheintal provider breadth, semantic reranking, multilingual intent packs, secure free-provider credentials and frontend server-answer integration. Current primary answer is still evidence-pack; candidate adds curated-answer-pack, not false generative availability.

## Next checkpoint — 2026-10-02 21:45 UTC
- Live recheck through WPVibe: all five mandatory queries still PASS (13/13/3/3/1), identical results for canonical/typo pairs. Evidence tests/results/v6-live-recheck.json. Live addon stays0.1.0; browser session now shows login, no deployment was attempted beyond opening the upload page. Existing connector remains authenticated.
- Added 5 community records /4 primary pages: Ludothek Altstätten borrowing, game boxes, on-site games/birthdays; Juniorband and Jungmusik Montlingen-Eichenwies. Candidate external batch data/v6-community-services-delta.json. Does not silently classify borrowing/music as entertainment centres or sport. Primary-source concise original four-language summaries. Two direct HTTP200/hash receipts, two timeouts marked fetch_status=error. Source web evidence reviewed; no copied rosters or staff contacts.
- 16 actual plugin-hook freshness checks PASS: edit/delete, debounce, active imports, concurrent edits, failures, empty index, retry, manual refresh. Manual build now clears dirty/error only for its captured revision; newer edits remain scheduled. Cron acceptance live still pending.
- Candidate metrics: 20 activity +13 life +5 community services; 11+11+4 source-page entries; 232 unreviewed document discoveries. Do not add these candidate counts to live4523.
- Existing unit suite PASS. Community search suite29 checks, including four languages and preventing mandatory-intent contamination. Latest package rebuilt after freshness fix.
- Next: deploy addon0.1.2 through supported upload when authenticated UI available; import bundled life, then reviewed community via external dataset (new hosts need supported core approval). Continue primary provider breadth and source revalidation; server generative inference still needs free-provider secure credentials. Never replace production core0.2.3 with candidate.

## Atomic index protection — 2026-10-02 21:50 UTC
- Found silent SQL-write failure and empty-data commit risk in baseline/candidate Knowledge::rebuild. Fixed both candidate core and isolated addon: verify all three index tables use InnoDB before deletes, check begin/delete/record/batch/commit results, roll back on any failure or empty result, publish stats only after successful commit. New tables request InnoDB explicitly. No live core or DB schema was modified.
- 22 checks PASS against actual SQLite transactions via wpdb adapter, for both candidate classes: begin/delete/record/terms/deletions/commit failure, duplicate IDs, empty data and nontransactional engine rejection preserve old data/stats; successful replacement publishes complete index. MySQL/InnoDB live transaction acceptance remains pending deployment.
- Package0.1.2 rebuilt with these safeguards. On deployment, verify table engine via supported operator/admin diagnostics; unsupported engine must fail safely rather than delete existing data.

## Latest continuation — 2026-10-02 22:00 UTC
- Latest candidate is **addon0.1.3**, package release/oberhub-v6-0.1.3.zip. Older0.1.2 retained as history. Production core0.2.3/addon0.1.0 unchanged; no new candidate deployment claimed.
- Addon now enqueues assets/server-answer.js only when V6 ready. Integrates into observed live core0.2.3 search markup without changing its files. Same /answer POST {question,lang} for every device; no local model/capability/context input. Clearly labels curated vs genuinely generated mode, plain-text safe output and HTTPS source links. Cancellation/sequence guards prevent stale responses. Existing options retained on failure. Candidate core0.3 also has server UI; addon avoids existing .server-answer. No external generative provider configured.
- Browser QA16 checks PASS at360/1920 width, same request payload, provenance links, honest modes, text-only rendering, unsafe-URL filtering, fallback/error preservation, accessible status, stale response cancellation. Run npm run test:v6:ui; current workspace requires OBERHUB_CHROMIUM_PATH=/workspace/scratch/cebcae155ab2/chromium-runtime/chromium. Official installed @sparticuz binary/SwiftShader files were unpacked locally because shared /tmp/chromium was empty. No browser dependencies committed.
- Live browser confirmed Russian sport query displays KiRi, STV Jugi and TSV youth offerings. Existing primary visible summary is record description; optional local AI button exists. Addon0.1.3 server UI is candidate, not live yet.
- Harvest offset36 completed:13 attempts,2HTTP200,1 discovery. Total233 unique unreviewed links,0 verified forms. Next offset48. Atomic per-page receipts retained.
- Candidate data remains38 services (20activity+13life+5community) across26 source entries;20 activities already live. Community external import needs supported approval for new provider hosts before enqueue. Life bundled path approves only its reviewed sources.
- Next concrete actions: deploy only0.1.3 via supported authenticated upload; prepare batch life, finish queue and build isolated index; verify five mandatory and31 life queries, UI answer mode and cron freshness; then reviewed community import. Continue breadth, direct source revalidation and server semantic reranking. Secure free-provider configuration/inference acceptance remains pending, not solved by curated packs.

Latest package SHA256: 76a0ba515b553f29e281df1c4d667ce4ccd5c599a70fc30878abb23b9a65a628
