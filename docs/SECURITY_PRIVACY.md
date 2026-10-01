# Security and privacy configuration

Application controls implemented and exercised in local WordPress:

- administrator/manage_options required for all Hub content and admin operations; editor role denied;
- import, export and settings use WordPress capabilities and nonces; validation before database mutations;
- public index/calendar GET; optional AI POST requires authenticated administrator and REST nonce;
- untrusted fields escaped at output, source URLs HTTPS-only on an exact domain allowlist; no credentials, ports other than 443, local hosts or redirected fetches;
- source monitoring uses `wp_safe_remote_get`, bounded response size, timeout and conditional validators; a change flags review, never rewrites legal facts;
- XML-RPC methods disabled, public user enumeration removed, author pages 404, public registration/comments/pings disabled;
- Two Factor 0.17.0 supports TOTP and recovery codes; Hub restricts administrators to these providers; after enabling public indexing, unenrolled administrators are redirected to their profile;
- failed password authentication limited to five attempts per IP-derived keyed hash in a 15-minute transient; security incidents are recorded separately;
- public CSP (scripts/connect/fonts self, objects none), nosniff, same-origin frame policy, referrer policy and restricted permissions;
- HSTS opt-in only on stable HTTPS; no automatic preload/subdomain inclusion;
- SQL containing variable input uses WordPress insert/update APIs or `$wpdb->prepare`;
- public pages stay noindex while actual operator/contact/address/host/country are incomplete.

Server controls included as configuration examples, **require a real host before they can be tested**: TLS and HTTP redirect, admin SSL, file editor disabled, no debug output, no directory listing, `.git`/`.env`/wp-config blocked, proper file permissions, isolated DB credentials, unique salts, uncached admin, cron, backups and host-log retention. Do not imply these were verified against a production server that does not yet exist. The release does not contain live credentials or enrolled production TOTP secrets.

## Data flow

Search and routing execute in the browser against a local curated index. Names, addresses and free text remain in memory/DOM. There is no server-side email sender, form submission, email queue, dialog store, localStorage or sessionStorage. Copy writes the local clipboard only; mailto opens the visitor's own mail client with an editable draft. The Hub does not send messages itself.

Analytics receive only allowed event names and canonical IDs/known language/locality values. The application does not record raw queries, request bodies, query strings, cookies, auth headers, generated emails or draft fields. Unknown paths are logged as a category to avoid copying arbitrary text. Minimal technical logs contain UTC time, IP, method, normalized path, HTTP status, bounded UA and referrer domain. They are private admin data.

| Data | Maximum default live retention | Removal |
|---|---|---|
| Request/IP records | 7 days | daily oh_daily purge |
| Security incidents | 30 days | daily purge |
| Administrator audit | 180 days | daily purge |
| Aggregate counters | 13 months | daily purge |
| Login-limit transient | 15 minutes | expiry/successful login |
| Visitor drafts / raw search strings | never stored | clear button / page close |

The host may keep independent web/security logs and backups. Configure those separately and name the actual provider/country in Privacy. HTTPS does not itself make a third-party provider Swiss-hosted. No advertising trackers or third-party web fonts are loaded. Necessary WordPress login cookies are disclosed; a fictitious public consent wall is not added.

Optional AI is OFF. An approved HTTPS provider/model/key may be configured server-side for admin previews only. It receives an administrator's question plus allowlisted source context; output always requires review. Before enabling, establish legal basis, minimization, provider location/subprocessors and update the privacy statement. No browser bundle contains an AI key.

## Official legal verification on 2026-10-01

- [Federal DSG, SR 235.1](https://www.fedlex.admin.ch/eli/cc/2022/491/de)
- [EDÖB overview of the revised law](https://www.edoeb.admin.ch/de/das-neue-datenschutzgesetz-aus-sicht-des-edob)
- [St.Gallen Datenschutzgesetz, sGS 142.1](https://www.gesetzessammlung.sg.ch/app/de/texts_of_law/142.1)
- [Federal WSchG, SR 232.21](https://www.fedlex.admin.ch/eli/cc/2015/613/de)
- [IGE guidance on protected public signs](https://www.ige.ch/de/etwas-schuetzen/marken/vor-der-anmeldung/schutzvoraussetzungen/eintragungshindernisse/geschuetzte-oeffentliche-zeichen)

Public authority duties under cantonal law are not automatically represented as obligations of an independent private Hub. No municipal coat of arms, official flag or official affiliation is asserted. The logo is an original abstract five-point mark. GDPR is not described as automatically applicable merely due to access from the EU. No unverified article numbers or legal entitlement decisions are given. Hosting/operator facts remain owner inputs, not fictional placeholder identities.
