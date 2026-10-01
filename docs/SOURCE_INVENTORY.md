# Provenance inventory

Machine-readable inventory: `data/sources.json`; complete record-to-source relationships: `data/seed.json`. Dates are ISO; manual verification date 2026-10-01. Exact source URLs, publisher, authority level, TTL, verification scope, status and reuse notes are exported. HTTP status/hash are present only where an actual direct download succeeded; null/empty is not presented as an HTTP success.

A — public authorities; B — public institutions; C — organisations; D — community. In this MVP, administrative procedure facts use A sources; school, hospital and public transport facts use their official institution sources B. A page on a public website does not turn Hub into a public authority. Municipal directory routes are `routing-metadata`, not a complete legal procedure audit.

Key original sources:

| Subject | Source |
|---|---|
| Municipal service directory | https://www.oberriet.ch/dienstleistungen |
| Offices/contact routing | https://www.oberriet.ch/aemter |
| Registration after moving | https://www.oberriet.ch/dienstleistungen/23620 |
| Residence certificate | https://www.oberriet.ch/dienstleistungen/23627 |
| Einwohneramt | https://www.oberriet.ch/aemter/10674 |
| 2026 waste plan | https://www.oberriet.ch/online-schalter/101755/download |
| Five localities | https://www.oberriet.ch/5doerfer1gemeinde |
| Current Kilbi / Marvin event | https://www.oberriet.ch/aktuellesinformationen/2996749 |
| Primary school moving registration | https://www.orschulen.ch/primarschule-ekmo/e.html |
| School administration | https://www.orschulen.ch/kontakt-227.html |
| Montlingen holiday dates | https://www.orschulen.ch/ferienplan-se-montlingen.html |
| EKMO day care | https://www.orschulen.ch/tagesstrukturen-ekmo.html |
| RAV districts | https://www.sg.ch/wirtschaft-arbeit/arbeitslos-arbeit-finden/rav.html |
| Transport | https://www.sbb.ch/de |
| HOCH medical emergency | https://www.h-och.ch/notfall/ |
| Music-school contact | https://msor.ch/kontakt/ |

Only normalized facts and original short summaries are included. Original municipal PDF, full downloaded website prose and protected images are not redistributed. Private research extraction text is excluded from the release and Git bundle. The factual inventory includes all 94 records and source links even when unknown procedural fields remain null. No source image/logo/coat of arms was copied.

TTL defaults: procedures 30 days, events/waste daily source check, school calendars 7 days, legal/source documentation 90 days. Editorial review is required after a source change. Source availability checking does not automatically assert that every fact is legally current.
