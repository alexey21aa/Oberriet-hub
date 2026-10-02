import fs from 'node:fs';
import assert from 'node:assert/strict';
const raw=fs.readFileSync('data/seed.json');
const seed=JSON.parse(raw);
const singles=seed.intents.filter(x=>x.type!=='compound-navigation-goal');
const compounds=seed.intents.filter(x=>x.type==='compound-navigation-goal');
assert.ok(singles.length>=2595);assert.ok(compounds.length>=2548);assert.ok(seed.intents.length>=5143);
assert.equal(new Set(seed.intents.map(x=>x.id)).size,seed.intents.length);
assert.equal(new Set(compounds.map(x=>`${x.service_id}:${x.tasks.join('+')}`)).size,compounds.length);
const answers=new Map(seed.answers.map(x=>[x.id,x]));
const sources=new Set(seed.sources.map(x=>x.source_id));
for(const goal of compounds){
 assert.equal(goal.tasks.length,2);assert.equal(goal.answer_ids.length,2);assert.equal(new Set(goal.answer_ids).size,2);
 assert.equal(goal.phrases.length,4);assert.deepEqual(Object.keys(goal.localized_phrases).sort(),['de','en','ru','uk']);
 goal.answer_ids.forEach((id,index)=>{const answer=answers.get(id);assert.ok(answer,`Missing ${id}`);assert.equal(answer.service_id,goal.service_id);assert.equal(answer.kind,goal.tasks[index]);assert.ok(sources.has(answer.source_id));});
 assert.equal(goal.factual_details_verified,false);
}
assert.equal(seed.events.find(x=>x.id==='marvin26').date,'2026-10-02');assert.equal(seed.events.find(x=>x.id==='marvin26').end_date,'2026-10-02');
assert.ok(seed.answers.length>=2548);assert.ok(seed.service_paths.length>=2548);assert.ok(seed.services.length>=364);
assert.ok(seed.aliases.length>=71344);assert.ok(seed.organizations.length>=35);assert.equal(seed.faqs.length,6);
assert.equal(seed.knowledge_metrics.single_subject_task_intents,singles.length);assert.equal(seed.knowledge_metrics.compound_navigation_goal_intents,compounds.length);
assert.ok(raw.byteLength<30*1024*1024);
assert.deepEqual(raw,fs.readFileSync('data/knowledge.json'));assert.deepEqual(raw,fs.readFileSync('wp-content/plugins/oberhub-core/seed.json'));
console.log(JSON.stringify({canonical_intents:seed.intents.length,single:singles.length,compound:compounds.length,aliases:seed.aliases.length,answer_reference_checks:compounds.length*2,seed_bytes:raw.byteLength,status:'passed'}));
