# Source inventory 0.2.0

Authoritative exports: `data/sources.json` (293 records) and `data/source-records.csv`; every service/answer links via source_id and the official source URL. The corpus includes 258 newly directly fetched official pages from ch.ch, SVA St.Gallen and Hallo SG with HTTP status/content hashes/date, plus the recovered 35 original sources. Original municipal/waste/contact dates are retained rather than falsely renewed.

Metadata includes authority/name, domain/type, language, public-source trust level, last_checked/date_added, verification status, geographic coverage and related service IDs. `routing-metadata` means the title/destination was verified: it does not certify every fee/document/procedure on the page. `page` and `page-subscenario` identify richer original guidance. Translation drafts remain drafts.

`data/expansion/verified-pages.json` and `hallo-pages.json` retain factual verification receipts, not mirrored articles. `scripts/build-knowledge.py` uses the verified evidence and curated translations. Current final `data/seed.json` additionally includes offices/FAQ and fuller provenance.

Source-health checks use approved HTTPS hosts, no redirects, bounded body/time and rotating batches; a change requires human review. They never replace legal/administrative facts automatically. The table's `checked` review state corresponds to verification; incomplete/outdated source or translation states remain visible.

Coverage includes Oberriet administration and institutions, schools, waste/current local calendar, regional hospital/transit, federal administrative topics, cantonal integration/housing/work/education/health guidance and SVA social insurance. No fabricated business, doctor, phone, fee, deadline or waste date was added to meet quotas.
