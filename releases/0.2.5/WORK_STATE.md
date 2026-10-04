# WORK_STATE — 0.2.5

Captured production: Core 0.2.3; addon 0.2.4; 16550 POI; 5828 index records; Groq configured/working; completed queue 329/329.
Release target: Core 0.2.3 (module source override); addon 0.2.5.
Deploy: pending. Production acceptance: pending.

Changed files: Knowledge.php, UniversalSearch.php, LocalEntities.php, LiveFallback.php, QueryUnderstanding.php, AI/Gateway.php, oberhub-v6.php, assets/server-answer.js, query-concepts.json.
New files: IntentPlan.php, SearchPolicy.php, SearchEngine.php, assets/core-app.js, artifact-actions.json.

| Query | Native snapshot + full discovery count |
|---|---:|
| пицца | 45 |
| парикмахер | 263 |
| стоматолог | 34 |
| бассейн | 39 |
| сауна | 3 |
| спорт для детей | 13 |
| танцы для женщин | 3 |
| налоговая декларация шаблон | 1 |
| Anmeldung | 15 |
| Wohnsitzbestätigung | 1 |
| пиццаа | 45 |
| Friseurr | 263 |

Known gaps: unknown country on some source records; named parent absent for some geometry groups; direct tax action is official year selector; no performance p95 claim from a 12-query sample.
Source and ZIP hashes are supplied in the external release checkpoint to avoid circular hashes.
