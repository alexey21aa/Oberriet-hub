#!/usr/bin/env python3
"""Build server ontology assets from canonical concepts; metadata is never an offer."""
import collections,json
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]
concepts={r['id']:r for r in map(json.loads,(ROOT/'data/v7/ontology/concepts.jsonl').read_text().splitlines())}
terms=collections.defaultdict(set);labels=collections.defaultdict(dict)
for a in map(json.loads,(ROOT/'data/v7/ontology/concept_aliases.jsonl').read_text().splitlines()):
 terms[a['normalized']].add(a['concept_id'])
 if a['kind']=='label':labels[a['concept_id']][a['lang']]=a['term']
lengths=collections.defaultdict(list)
for term in sorted(terms):lengths[len(term)].append(term)
pack={'schema_version':2,'mode':'ontology-query-expansion','device_independent':True,
 'concepts':{cid:{'labels':labels[cid],'parent_id':c['parent_id']} for cid,c in concepts.items()},
 'terms':{term:sorted(ids) for term,ids in sorted(terms.items())},
 'terms_by_length':dict(sorted(lengths.items())),
 'counts':{'concepts':len(concepts),'terms':len(terms)},
 'provider_facts_asserted':False,'discovery_objects_included':0}
out=ROOT/'wp-content/plugins/oberhub-v6/ontology.json';out.write_text(json.dumps(pack,ensure_ascii=False,separators=(',',':'))+'\n')
print(json.dumps(pack['counts']))
