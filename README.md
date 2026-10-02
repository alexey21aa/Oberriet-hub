# Oberriet Hub · WordPress / Core 0.2.1

Independent civic navigation for Oberriet, Montlingen, Kriessern, Eichenwies and Kobelwald. DE / EN / UK / RU. Updated 2026-10-01.

The existing MVP is deployed at https://oberriethub.ch. WordPress 7.1.2 / PHP 8.4.26 run OberHub Theme 0.2.0, OberHub Core 0.2.1 and WPVibe 1.20.0. The baseline import completed 3,290 records and its search index; live REST search returned the residence-certificate service in DE, EN, RU and UK. The private GitHub repository alexey21aa/Oberriet-hub currently preserves the source ZIP and full Git bundle; importing the bundle as native commits is still pending. No payments were made.

## Included

WordPress 7.1.2, original block theme `oberhub-theme` 0.2.0, `oberhub-core` 0.2.1 and Two Factor 0.17.0. No paid plugin, query API, advertising tracker or external font is required.

| Corpus | Count | Meaning |
|---|---:|---|
| Distinct services and scenarios | 364 | 94 originals + 270 additional official navigation/practical records |
| Service-task paths | 2,548 | Seven explicit navigation facets per service; not 2,548 separate municipal services |
| Structured navigation answers | 2,548 | Four languages; unknown fees/documents/deadlines explicitly remain unknown |
| Canonical intents | 5,143 | 2,595 single-task/curated intents + 2,548 explicitly typed compound navigation goals |
| Query formulations | 71,344 | Paraphrases counted as aliases, not independent facts |
| Source records | 293 | 258 newly checked official URLs with HTTP status/hashes |
| Offices/institutions and contacts | 35 / 35 | Institution/office entities derived from existing verified contact records |

Each compound goal combines two meaningful tasks and references exactly two existing source-backed navigation answers. Compounds add no services or factual answers. All four numerical minimum targets are met.

Details: `data/knowledge-metrics.json`. Translations are editorial drafts. URL/headline verification is navigation verification, not confirmation of every entitlement, tariff or procedure on that source. The local source date is shown on every answer.

## Install

Read `docs/DEPLOYMENT_RU.md`. For a new installation upload only `wordpress/` into your Swiss PHP/MariaDB document root and use the WordPress installer. For an existing site use the theme/core/Two Factor ZIPs. Keep source, tests, Git bundle and backups outside the public directory.

Activate Two Factor, OberHub Core and the theme. The large initial corpus is queued privately; open **Oberriet Hub** in wp-admin to import in resumable batches. Wait until both records and index report **complete**. A setup screen prevents incomplete content from appearing live. Configure operator identity, real host/country, HTTPS, TOTP/recovery codes, cron and backups before enabling indexing.

## Search and privacy

The server uses indexed weighted Unicode tokens and typo signatures, deterministic navigation answers, source/locality relevance and bounded pagination. Main search uses read-only POST; queries are not saved in Hub analytics or put into the request URL. Contact routing runs in the browser. Names, addresses and drafts remain in the page, with copy/mailto only.

Optional native browser AI is activated only by a visitor click where `LanguageModel` is exposed and its model is already available. No automatic model download or API billing. The adapter returns a separate unverified draft and preserves deterministic results on failure. Adapter/fallback tests pass; actual hardware model inference is not claimed tested. WebLLM was researched but is not a shipped dependency.

## Development and handover

`npm ci`; `npm test`; `npm run test:search`; `npm run test:queue`; `npm run test:local-ai`. For WordPress QA, set `OBERHUB_RUNTIME` to this project's absolute directory and `OBERHUB_CHROMIUM_PATH` to a working browser binary, then `npm run test:wordpress`. Development dependencies do not belong in production.

The package contains source, import data, provenance exports, restored Git history, installer ZIPs, QA results, previews, architecture, configuration/server examples and operating/backup instructions. Inspect `docs/QA_REPORT.md` and `docs/KNOWN_LIMITATIONS.md` for the exact verification scope.

To repack from the included source, set `OBERHUB_WORDPRESS_CORE` to the included `wordpress/` directory (or an official core ZIP) and run `python3 scripts/build-release.py` in a Git checkout with final evidence committed. The archive contains the complete data used for installation; regeneration of source harvests requires fresh source verification.


## Source freshness update · 2026-10-02

Core 0.2.1 adds bounded official-source refresh, conditional fetches, per-host limits, robots/path rules and opt-in ingestion. Automatic fetch dates and editorial review dates are separate. The search UI shows service evidence and a separate original-language document section. Production enables metadata-only ingestion for the municipal `/aemter` and `/dienstleistungen` roots; the first two document records were verified through WPVibe.

Operator identity is not configured, so the Hub remains excluded from search-engine indexing. Recovery codes still need the owner's private setup. Hosting control-panel backup and scheduled cron execution have not yet been verified. The expanded regional corpus is undergoing validation; local expansion metrics must not be reported as deployed counts until its import completes.
