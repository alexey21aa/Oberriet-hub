# Known limits and Phase 2

Operational MVP is delivered as an installation package, **not a live site**. No production domain/Swiss host/database was supplied and WPVibe has no connected site. No repository was accessible to the GitHub connector and it has no repository-creation operation. A complete local Git bundle is provided; remote import requires an authorized target repository.

Data is verified as of 2026-10-01. 94 service/guide entries include 76 municipal catalogue routes and 18 richer original summaries/local institution guides. Most catalogue routes deliberately leave price, eligibility, processing time and document lists unknown until verified. Directory validation proves the service/contact routing, not every procedure in its full source page. `verification_scope` differentiates this. Do not present completeness of the directory as legal completeness.

EN/RU/UK text exists for all services and UI, but is an editorial draft. Native institution names remain German. German email templates are ready to edit: arbitrary foreign-language free text is explicitly retained as original rather than silently misrepresented as a machine translation. A short broken-light message has a German deterministic formulation. No automatic translator is required or activated.

Waste data is valid for 2026 only. Fixed paper dates are the remaining dates from the official plan at the research date. Household/cardboard weekly routes are known for Oberriet, Montlingen, Eichenwies and Kriessern. Kobelwald paper is mapped to Berggebiet with a route-confirmation notice; ordinary refuse is not inferred there. Green collection rounds were discontinued; self-delivery is linked. On expiry, unknown dates are shown and the official plan remains available. Calendar has four verified events/periods plus computed waste events, not a fabricated full local-events feed. ICS uses all-day dates; timed events/timezone export needs a later extension.

Official St.Gallen OpenData API documentation was inspected: [shared mobility dataset/API](https://daten.sg.ch/explore/dataset/stationsbasierte-shared-mobility-angebote-im-kanton-stgallen/api/). No verified Oberriet-specific automated waste/events feed suitable for this MVP was found. Therefore the application uses the source registry, human-curated facts and monitored changes instead of inventing an endpoint or inferring unsupported local records. Dataset licensing must be recorded before any future dataset import.

WordPress 7.1.2/PHP 8.3 was actually run in Playground with its SQLite adapter. The production target is MariaDB/MySQL; a real-host smoke test and HTTPS/file/cache checks are required before launch. The suite covers Chromium desktop/mobile viewport emulation, not physical Android/Samsung/Edge devices. Firefox and WebKit downloads were unavailable in this environment; their checks are explicitly skipped, not passed. Full WCAG certification and an independent penetration test are not claimed.

The admin editor is the native WordPress CPT screen with validated structured JSON. It is functional and revision-supported, but a friendlier field-by-field editor is a reasonable Phase 2 improvement. `oh_guide` exists; current navigation guides are searchable Services. Import is validated before starting but is not a cross-database transaction; on a DB failure, restore/retry from backup. No automated content deletion by missing ID.

## Phase 2, after launch

- Official Gemeinde review, approved data feeds and clear ownership of corrections.
- Direct authorised Mängelmelder tickets; AGOV/E-Login only with official integration.
- Timed ICS events, additional verified calendar feeds, 2027 waste plan.
- Professional review of EN/RU/UK and optional privacy-approved translation.
- Friendly admin fields, richer editorial approval/revision UI and cache purge integration.
- Real Firefox/WebKit and physical mobile testing; full manual accessibility audit.
- Push/PWA offline, richer maps/community/business directory only after verified source rights.
- Advanced monitoring/B2G dashboards and enterprise SLA only when justified.

## Exact items requiring Gemeinde cooperation

1. Confirm streetlight fault recipient and emergency/non-emergency escalation for each locality; current Hub routes uncertainty to the general administration.
2. Confirm Kobelwald/Holzrhode refuse district, exceptional holidays and route-specific collection details.
3. Supply maintained waste/calendar feeds, reuse rights, source contacts and the next year's plan; agree correction cadence.
4. Review administrative fees/documents/eligibility and authoritative German summaries; approve translations separately.
5. Authorise any official integration, branding/insignia, ticket API, AGOV or exchange of residents' data. Hub uses none of these implicitly.
6. Identify responsible staff and response expectations if a formal cooperation agreement is made. Do not promise service levels on their behalf.
