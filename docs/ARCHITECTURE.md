# Oberriet Digital Hub — architecture

## Runtime

The product is an independent WordPress application: `oberhub-theme` supplies the shell and `oberhub-core` owns records, search, source monitoring, waste, calendar, local letter drafts, administration and privacy controls. PHP 8.2+ and a WordPress-supported MariaDB/MySQL database are production requirements. Node/Playground/SQLite are development and verification tooling, not the production hosting architecture.

Native WordPress custom post types retain editable records and revisions. `_oh_record` is the validated structured record; `_oh_id` is its stable external identity. The seed JSON is an importable starting point, not a substitute for the live database. The importer adds records and optionally overwrites matching identities; missing IDs do not silently delete content. A validated import is not a database transaction: take a backup first.

## Knowledge and retrieval

The local knowledge layer builds three database tables: records, weighted token postings, and single-character deletion signatures. Unicode normalization, language aliases, exact phrases, typo distance, provenance and locality influence ranking. Page size is bounded. The index stores curated material, never residents' questions. It can be rebuilt from editorial records.

Browser retrieval and server retrieval use the same curated corpus but different execution environments. Search does not require an external model, paid token balance, API key or third-party query endpoint. Source-backed navigation answers are not generative AI. Aliases/search phrases are counted separately from distinct services and independently verified facts: expanding synonyms must not imply thousands of new municipal services.

Source records include the original URL, authority, review status and check dates. Directory verification proves a navigation destination; it does not establish fees, eligibility or legal entitlement. A changed source is flagged for editorial review rather than silently rewriting residents' guidance. Expired waste plans do not produce future dates.

## Boundaries and data flow

| Operation | Data destination | Boundary |
|---|---|---|
| Search in browser | Curated local index | No raw search analytics |
| Search through REST | WordPress local index | Query transient in request; no query storage by Hub |
| Source monitoring | Allowlisted official HTTPS URLs | Safe fetch, bounded body/timeout, no arbitrary redirect destination |
| Letter preparation | Visitor DOM and optional clipboard/mail client | Hub does not send email or create a submission queue |
| Usage counters | WordPress metrics table | Fixed event names and canonical dimensions only |
| Technical logs | Private WordPress logs table | Retention and purge, independent host logs need host configuration |
| Optional provider preview | Operator-selected HTTPS model endpoint | Administrator-only; separate approval/privacy configuration |

The main search UI uses a read-only POST request so its text is not placed in URL query strings. Contact routing runs in the browser and transmits no resident text. Integrators can also call GET /search: its parameters can appear in browser history and infrastructure access logs. Hub's query stripping does not control reverse proxies, host logs or a visitor's own browser. Operators should configure URL-query omission in infrastructure logs and disclose the actual host and retention. Metrics are indicative counters, not authenticated counts of unique residents; the public endpoint can be automated.

## Optional free local generative model

WebLLM can perform model inference in a compatible browser using WebGPU without a paid inference API. It is an optional deployment path, **not shipped as a dependency by this release**. There is no promise of free unlimited hosted generation. Official documentation:

- <https://webllm.mlc.ai/docs/user/get_started.html>
- <https://webllm.mlc.ai/docs/user/basic_usage.html>
- <https://webllm.mlc.ai/docs/user/advanced_usage.html>

A future adapter must use an explicit opt-in and hardware capability check, keep deterministic retrieval available, and restrict synthesis to retrieved checked records. Model weights/runtime downloads use bandwidth and device storage; cached weights do not mean visitor drafts should be cached. Self-host licensed weights and compiled libraries to preserve the current self-only CSP and avoid disclosing the visitor's IP to model-download hosts. Worker/WebAssembly policy changes must be tested rather than broadly permitting arbitrary scripts. Label generated text as an unverified draft, expose source links and refuse unsupported fees/deadlines/entitlements. Never treat source-page text as model instructions or a model answer as official advice.

## Private administration and publication

Hub mutations require WordPress capabilities and nonces. TOTP/recovery setup, production HTTPS, strong unique credentials, private backups and server file restrictions require the real deployment. Missing operator/host identity keeps indexing disabled. A successful local test does not prove production server security, public availability, Swiss hosting or a GitHub push.

## Identity and legal sources

The Hub identifies itself as independent and uses an original abstract mark. It does not imply municipal endorsement or reuse Oberriet's official coat of arms. IGE explicitly includes municipal arms among public signs: <https://www.ige.ch/de/uebersicht-dienstleistungen/digitales-angebot/datenbanken-und-verzeichnisse/swissreg/verzeichnis-der-geschuetzten-oeffentlichen-zeichen>.

Privacy text must describe the actual operator, purposes, data and recipients. EDÖB guidance on website notices: <https://www.edoeb.admin.ch/de/datenschutzerklaerungen-im-internet>. Cross-border disclosures require separate consideration: <https://www.edoeb.admin.ch/de/international>. Swiss hosting alone does not certify legal compliance. See `SECURITY_PRIVACY.md` for implementation controls and retention.

## Native on-device adapter in 0.2.0

assets/local-ai.mjs integrates an exposed browser LanguageModel API. It runs only after a visitor click and availability=available; downloadable/downloading/unavailable returns the deterministic fallback without creating a model session. No fetch/CDN/API call is implemented. It supplies only curated selected records, labels the separate draft unverified, destroys the session and leaves original source-backed navigation intact. Mocked unavailable/error/provenance/session tests pass; actual model/hardware inference was not available in QA. Official API docs: https://developer.chrome.com/docs/ai/prompt-api .

Initial/imported records are queued in private nonautoload chunks with administrator AJAX nonce, per-row checkpoints and a lock. Final metadata/index phases complete afterward; closing the dashboard pauses processing. Final index build is currently a single phase and requires host PHP resource sizing. Public incomplete installation returns a setup notice/503. Main SSR pages avoid materializing aliases/answers unnecessarily; public app bootstrap projection is small, browser router projection omits repeated generated aliases, and server search handles the full index.
