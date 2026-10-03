# V7 universal intelligence — candidate checkpoint

This extends the restored V6 project. Production is NOT replaced. Current active
Core is 0.2.3 and addon is 0.1.0; latest undeployed V6 package remains 0.1.7.

## Separate registries

- `data/v7/ontology/concepts.jsonl`: canonical taxonomy candidates, stable IDs,
  parents, classification tags and pinned revision. Semantic cross-taxonomy audit
  is pending. Terms, translations and facets do not inflate concept totals.
- `concept_aliases.jsonl`: EN/DE/RU/UK lexical aliases. Shared terms keep multiple
  IDs. No duplicated records for device capabilities or typo spellings.
- `data/v7/business/business_entities.json`: public OSM POI discoveries, stable
  source IDs and receipt hashes. They are NOT verified companies or offerings.
- `data/v7/graph/business_entities.jsonl`: discoveries plus reviewed provider
  locations. Shared domains/phones never automatically merge branches.
- `business_offerings.jsonl`: existing reviewed V6 activities, linked to providers;
  20 offers are already live; four new medical drafts remain candidate-only and need canonical review. Verification expires
  outside a 7-day source-review window. Customer hours and employer shifts are
  separate and unverified.

## Reproduce and continue

```sh
python scripts/build-universal-concepts.py --fetch
python scripts/harvest-v7-businesses.py --max-municipalities 1 --timeout 35
python scripts/harvest-v7-businesses.py --retry-failed --max-municipalities 1
python scripts/build-v7-business-graph.py
python tests/v7-intelligence.py
```

The pinned raw taxonomy is fetched to an uncommitted cache. Output receipts include
SHA256 and upstream commit. Discovery jobs are serial, resumable and stop on quota
errors, retaining earlier successful results. A 5-minute persisted cooldown
prevents immediate retries. Rüthi's exact administrative name did not resolve;
this is not evidence of zero businesses. Resolve the boundary from public Swiss
administrative identifiers before retrying. Do not guess eligibility or services.

`v7_intelligence.py` provides bounded server candidate query understanding,
Unicode normalization, one-edit recovery, typed age/free/location/open-now intent,
classification joins, geographic selection and identity-review signals. It is
NOT a deployed endpoint or semantic model. Open-now intent is not an opening fact.
Unknown coordinates cannot produce local distances. Ambiguous boundary membership
must be reviewed. Old first-harvest boundary memberships may be incomplete; a
subsequent boundary reconciliation is required before precise local claims.

## Remaining acceptance gaps

491 concept candidates (484 taxonomy + 7 primary-reviewed health distinctions) / 7,591 language+normalized-term pairs are not the V7
25,000 / 100,000 goals. Zero canonical concepts have completed cross-taxonomy
semantic audit. NOGA 2025, medical/legal/jobs/human-needs concepts and licensed
Swiss registry ingestion remain to be added. Metadata discoveries require primary
provider review before publication. Semantic reranking and actual server-side
generative inference remain pending; curated evidence packs are not generation.


## Primary medical review continuation

`python scripts/add-v7-reviewed-medical.py` reproduces four editorial candidate
medical offerings from six captured HTTP200/hash receipts and three reviewed
providers. It does not diagnose, promise clinical effectiveness or insurance
coverage. Seven distinct health needs extend the lexical registry with four
languages. `reviewed_offering_candidates` joins offers by concept and rejects
expired/future source evidence. New offerings remain ineligible for live import
until full live-record canonical comparison. No opening-now claim is produced.

`python scripts/build-v7-review-queue.py` preserves180 entity-review jobs /175
unique candidate URLs, with3 primary-content reviews done and177 pending.
Website metadata alone is not provider identity.420 POIs lack website references.
The NOGA queue records22 sectors/87 division-code URLs observed from the official
2025 KUBB page, without importing bulk labels or inventing businesses.109 code
references do not add109 canonical concepts. Bulk reuse/license review is pending.
The municipality metadata attempt returned504; independent reviews continued.
