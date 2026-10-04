# 0.2.5 — Search architecture fix

Based on the live 0.2.4 addon and current Core 0.2.3 entry point. No data import, deletion, index rebuild, provider replacement, or legal-page changes.

- Shared result policy resolves native services, provider websites, public map objects, and direct official artifacts before rendering.
- The addon overrides the Core module URL with a patched copy of the current entry point. Core files and its version remain 0.2.3; addon rollback restores the original Core module.
- 20 results per page, server maximum 25, cached pagination, result counts, types, trust badges, locality/type filters, and visible geographic expansion.
- Staged ranking keeps known CH local results ahead of cross-border entries and web/directory results. Website and text scores cannot move cross-border results into the local lane.
- Offering/category retrieval for food, beauty, repairs, tyres, dental providers and leisure. Narrow products require offering/name/tag evidence.
- RU/UK official and clinical domain recognition, CH/SG defaults, tax return anchors and direct official form selector.
- Record-side concept interpretation uses exact aliases to prevent one-edit collisions such as Ukrainian “початкова” becoming “податкова”. Query-side typo support remains.
- Duplicate and parent/proximity geometry collapse; short summaries; compact AI context; existing Groq configuration retained.
- Local retrieval returns before optional live fallback. Search/AI/fallback cache namespace bumped to 0.2.5.

Validation: PHP syntax, both JavaScript entry points, compact 12-query matrix on the captured current native-service snapshot and full 16,550-record discovery corpus, no discovery internal links in API targets, cached nonoverlapping pagination.

Limits: native snapshot fixtures include services/contacts/places; organizations and the complete addon DB are checked during production acceptance. Unknown country remains explicitly unknown rather than guessed. Some pool components lack a named parent in the source corpus. The tax action is an official year selector, not a fabricated PDF/template. Browser preview must be verified on production; local Chromium could not be downloaded in this runtime.
