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
  these 20 offers are already live, not newly imported. Verification expires
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

484 taxonomy candidates / 7,563 language+normalized-term pairs are not the V7
25,000 / 100,000 goals. Zero canonical concepts have completed cross-taxonomy
semantic audit. NOGA 2025, medical/legal/jobs/human-needs concepts and licensed
Swiss registry ingestion remain to be added. Metadata discoveries require primary
provider review before publication. Semantic reranking and actual server-side
generative inference remain pending; curated evidence packs are not generation.
