import fs from 'node:fs';
import assert from 'node:assert/strict';
import {createSearchIndex,searchIndex,normalise,distance} from '../wp-content/plugins/oberhub-core/assets/search-engine.mjs';
const seed=JSON.parse(fs.readFileSync(new URL('../data/seed.json',import.meta.url)));
const index=createSearchIndex(seed);
for(const [q,lang,id] of [['Wohnsitzbestätigung','de','23627'],['wo anmelden','de','23620'],['переезд','ru','23620'],['куда сообщить адрес','ru','23620'],['сміття Montlingen','uk','24176'],['garbage Kriessern','en','24176'],['school registration','en','school'],['фонарь не работает','ru','lighting'],['Steuererklärung','de','23986']]){
 const result=searchIndex(index,q,lang);assert.equal(result.results[0]?.record.id,id,q);console.log('PASS intent '+q);
}
assert.equal(searchIndex(index,'zyxqv987zzblorf','en').total,0);
assert.equal(normalise('STRAßE Zürich Ёж'),'strasse zurich еж');
assert.equal(distance('переезд','переезл'),1);assert.equal(distance('register','regsiter'),1);
assert.equal(searchIndex(index,'переезл','ru').results[0].record.id,'23620');
const big={services:Array.from({length:2000},(_,i)=>({id:String(i),title:{en:`Civic permit ${i}`},description_short:{en:'Municipal permit administration'},source_id:'official'})),intents:[{id:'distinct-intent',service_id:'501',phrases:['lost permit replacement']}]};
const start=performance.now(),large=createSearchIndex(big),built=performance.now()-start;
assert.equal(searchIndex(large,'lost permit replacement','en').results[0].record.id,'501');
const first=searchIndex(large,'municipal permit','en',{page:1,perPage:25}),second=searchIndex(large,'municipal permit','en',{page:2,perPage:25});
assert.equal(first.total,2000);assert.equal(first.results.length,25);assert.equal(second.results.length,25);assert.ok(!first.results.some(a=>second.results.some(b=>a.record.id===b.record.id)));
assert.equal(searchIndex(large,'municipal permit','en',{perPage:1000}).results.length,50);
console.log(`PASS 2000 real records, pagination, index build ${Math.round(built)}ms; 1 distinct intent (aliases not counted)`);

const {navigationKinds}=await import("../wp-content/plugins/oberhub-core/assets/app.js");
assert.deepEqual(navigationKinds("Какие документы нужны и сколько стоит Wohnsitzbestätigung"),["cost","requirements"]);
assert.deepEqual(navigationKinds("application deadline and online form"),["deadline","online","apply"]);
assert.deepEqual(navigationKinds("Wohnsitzbestätigung"),["overview"]);
console.log("PASS combined navigation goals render multiple source-backed facets");

for(const intent of seed.intents.filter(x=>x.type==="compound-navigation-goal")){for(const phrase of Object.values(intent.localized_phrases)){const selected=navigationKinds(phrase);assert(intent.tasks.every(task=>selected.includes(task)),intent.id+": "+phrase+" => "+selected);}}
console.log("PASS all four-language compound goals select both answer facets");
