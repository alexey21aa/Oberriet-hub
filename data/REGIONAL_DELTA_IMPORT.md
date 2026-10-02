# Regional primary-provider expansion

1. Import `regional-delta-add-only.json` **with overwrite disabled**. It adds 13 real programme/location services, 13 published professional contacts, 8 organizations/consultation locations, 8 independently fetched sources and 13 factual answers. It contains only their small additional intent/alias lists; the queue merges these when overwrite is disabled.
2. Wait for import and index completion.
3. Import `regional-corrections-no-metadata.json` **with overwrite enabled** to connect the existing integration directory card to its freshly verified regional contact. This file deliberately omits intents, aliases and waste; their options cannot be replaced.

The primary provider timetable supersedes an older secondary directory. Oberriet is listed at Gleis 1 on Wednesday 08:00–11:00 with Ukrainian/Russian/German help. Verify holiday exceptions with the published professional contact. No public event dates or waste schedules were changed.

Fresh source records keep editorial review separate from transport state, use UTC verification timestamps and bounded F3/F4 refresh policies. Pages containing identifiable staff contact details are excluded from AI ingestion. Evidence hashes and direct HTTP 200 responses are recorded in `data/expansion/regional-evidence/manifest.json`.
