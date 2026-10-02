import fs from 'node:fs';
import assert from 'node:assert/strict';
import {createSearchIndex,searchIndex,tokens} from '../wp-content/plugins/oberhub-core/assets/search-engine.mjs';
const delta=JSON.parse(fs.readFileSync(new URL('../data/v6-activities-delta.json',import.meta.url)));
const baseline=JSON.parse(fs.readFileSync(new URL('../data/seed.json',import.meta.url)));
const data={...baseline,services:[...baseline.services,...delta.services]};
const index=createSearchIndex(data);
const mandatory=['Спорт для детей','Спорт доя детей','Танцы для женщин','танци доя женщин','Развлекательные центры для детей'];
const cases=[];
for(const q of mandatory)cases.push({q,concept:q.includes('порт')?'sport':q.toLowerCase().includes('тан')?'dance':'indoor'});
const bases={sport:['спорт для детей','спорт для дитини','Sport für Kinder','sports for kids'],dance:['танцы для женщин','танці для жінок','Tanzen für Frauen','dance for women'],indoor:['развлекательные центры для детей','розважальні центри для дітей','indoor Kinder Freizeitzentrum','indoor entertainment for children']};
for(const [concept,queries] of Object.entries(bases))for(const q of queries){
 cases.push({q,concept});
 // One-edit deletions and transpositions across every significant word, plus mixed localities.
 for(const word of q.split(' ').filter(w=>[...w].length>=4))for(let i=0;i<[...word].length;i++){
  const chars=[...word],deleted=[...chars];deleted.splice(i,1);
  cases.push({q:q.replace(word,deleted.join('')),concept});
  if(i+1<chars.length){[chars[i],chars[i+1]]=[chars[i+1],chars[i]];cases.push({q:q.replace(word,chars.join('')),concept});}
 }
 for(const locality of ['Oberriet','Montlingen','Altstätten','Rheintal','Kriessern','Eichenwies','Heerbrugg','Widnau','St. Gallen','Buchs','Rebstein','Marbach','Rüthi','Balgach'])cases.push({q:q+' '+locality,concept});
}
const failures=[];
for(const {q,concept} of cases){
 const result=searchIndex(index,q,'ru',{types:['services'],perPage:5});
 if(!result.results.length||result.results.some(x=>!x.record.search_concepts?.includes(concept)))failures.push({q,concept,ids:result.results.map(x=>x.record.id)});
}
// A misspelled preposition must not change the evidence set.
for(const [a,b] of [['Спорт для детей','Спорт доя детей'],['Танцы для женщин','танци доя женщин']])assert.deepEqual(searchIndex(index,a).results.map(x=>x.record.id),searchIndex(index,b).results.map(x=>x.record.id));
const metrics={cases:cases.length,passed:cases.length-failures.length,failed:failures.length,mandatory:mandatory.map(q=>({q,ids:searchIndex(index,q,'ru',{types:['services'],perPage:5}).results.map(x=>x.record.id)})),failures};
fs.writeFileSync(new URL('./results/v6-search.json',import.meta.url),JSON.stringify(metrics,null,2));
console.log(JSON.stringify(metrics));
assert.equal(failures.length,0);
assert.equal(searchIndex(index,'zyxqv987zzblorf').total,0);
export {data,index,cases};
