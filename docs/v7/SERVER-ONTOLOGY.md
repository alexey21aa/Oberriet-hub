# V7 server ontology extension

V7 extends the existing V6 addon. Production core 0.2.3 is never included in the addon package.

`scripts/build-universal-concepts.py` combines the pinned licensed OSM schema, reviewed medical concepts, and original four-language human-needs labels. Brands, gender/place variants and query combinations do not generate concepts. Exact label/tag duplicates are collapsed; semantic cross-taxonomy audit is still pending. New nature, emergency, playground, vending, historic and public-transport categories are taxonomy concepts, not proof of a local offering.

`scripts/build-v7-server-pack.py` creates `ontology.json` for PHP. There are no OSM business discoveries or provider assertions in this pack. Counts of language-specific terms differ from the server's language-neutral keys.

`UniversalSearch` preserves successful V6 retrieval and pagination. For an empty V6 response, a unique complete ontology phrase (or one-edit whole-phrase typo) can retry server retrieval with translated labels. Ambiguous concepts and unknown phrases do not expand. Locality, type, requested language and pagination pass unchanged. German provider labels take precedence; partial result pages are never merged. Gateway and the public search use the same server-owned retrieval implementation. No browser memory/CPU capability is read.

This is multilingual lexical query expansion, not embedding semantic reranking. It does not solve noisy nonempty V6 results, complex free-text constraints, live business hours, incomplete provider coverage or external generative inference. Existing evidence and privacy gates still control what can reach an external AI provider.

The civic graph contains primary web-reviewed summaries. Failed direct receipts stay `fetch_status=error`, are not promoted to verified offerings, and remain excluded from reviewed graph retrieval/external AI. Two civic offers map to enriched existing live service IDs; do not create duplicate imports.

Reproduce:

```sh
python3 scripts/build-universal-concepts.py --fetch
python3 scripts/add-v7-civic-coverage.py
python3 scripts/build-v7-business-graph.py
python3 scripts/build-v7-review-queue.py
python3 scripts/build-v7-server-pack.py
python3 tests/v7-intelligence.py
node tests/v7-server.mjs
node tests/v7-fallback.mjs
npm run test:v6
python3 scripts/build-addon-release.py
```

Deploy only the latest addon ZIP through authenticated WordPress plugin upload. No data import is needed for ontology deployment, and no repeat activity import is allowed. Verify `/v6/status`, the five mandatory queries, new multilingual queries and `/answer`. A package in GitHub is not deployment.
