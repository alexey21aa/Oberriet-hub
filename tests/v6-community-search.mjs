import fs from 'node:fs';
import assert from 'node:assert/strict';
import {createSearchIndex,searchIndex} from '../wp-content/plugins/oberhub-core/assets/search-engine.mjs';
const data=JSON.parse(fs.readFileSync('data/seed.json'));
const community=JSON.parse(fs.readFileSync('data/v6-community-services-delta.json'));
for(const name of ['activities','life-services'])data.services.push(...JSON.parse(fs.readFileSync('data/v6-'+name+'-delta.json')).services);
data.services.push(...community.services);
const index=createSearchIndex(data);
const cases=community.services.flatMap(r=>Object.entries(r.title).map(([lang,q])=>({lang,q,id:r.id})));
for(const [q,slug] of [['прокат игр Altstätten','ludo-borrow'],['детский музыкальный ансамбль','juniorband'],['игровые наборы для праздника','ludo-boxes'],['Juniorband Montlingen','juniorband'],['Youth music Montlingen','jungmusik'],['день рождения Ludothek','ludo-play']])cases.push({lang:'ru',q,id:'v6-community-'+slug});
const checks=cases.map(({lang,q,id})=>{const ids=searchIndex(index,q,lang,{perPage:5}).results.map(x=>x.record.id);return{lang,q,expected:id,ids,passed:ids.includes(id)}});
// Borrowing games and music groups must not become indoor entertainment centres.
for(const q of ['Развлекательные центры для детей','Спорт доя детей','танци доя женщин']){const hits=searchIndex(index,q,'ru',{perPage:50}).results;checks.push({q,passed:hits.length>0&&hits.every(h=>!h.record.id.startsWith('v6-community-'))});}
fs.writeFileSync('tests/results/v6-community-search.json',JSON.stringify(checks,null,2)+'\n');
console.log(JSON.stringify({checks:checks.length,failed:checks.filter(x=>!x.passed)}));assert(checks.every(x=>x.passed));
