import fs from 'node:fs';
import assert from 'node:assert/strict';
import {createSearchIndex,searchIndex} from '../wp-content/plugins/oberhub-core/assets/search-engine.mjs';
const data=JSON.parse(fs.readFileSync('data/seed.json'));
for(const batch of ['activities','life-services','community-services','support-services','health-services'])data.services.push(...JSON.parse(fs.readFileSync(`data/v6-${batch}-delta.json`)).services);
const health=JSON.parse(fs.readFileSync('data/v6-health-services-delta.json'));
const index=createSearchIndex(data);
const concepts=JSON.parse(fs.readFileSync('wp-content/plugins/oberhub-core/query-concepts.json'));
assert.deepEqual(concepts,JSON.parse(fs.readFileSync('wp-content/plugins/oberhub-v6/query-concepts.json')));
assert.deepEqual(concepts,(await import('../wp-content/plugins/oberhub-core/assets/query-concepts.mjs')).default);
const cases=health.services.flatMap(r=>Object.entries(r.title).map(([lang,q])=>({lang,q,id:r.id})));
for(const [q,slug] of [['помощь при онкологических заболеваниях','cancer-advice'],['консультации по уходу за стомой','stoma-advice'],['паллиативная помощь на дому','palliative-home-care'],['форма паллиативной помощи','palliative-registration'],['палиативная помощь','palliative-home-care'],['паліативна допомога','palliative-home-care'],['Palliativpflege','palliative-home-care'],['stomaberatun','stoma-advice'],['стомою','stoma-advice'],['онкологічні консультації','cancer-advice']])cases.push({lang:'ru',q,id:'v6-health-'+slug});
const checks=cases.map(({lang,q,id})=>{const ids=searchIndex(index,q,lang,{perPage:5}).results.map(x=>x.record.id);return{lang,q,expected:id,ids,passed:ids.includes(id)}});
for(const q of ['Развлекательные центры для детей','Спорт доя детей','танци доя женщин']){const hits=searchIndex(index,q,'ru',{perPage:50}).results;checks.push({q,passed:hits.length>0&&hits.every(h=>!h.record.id.startsWith('v6-health-'))});}
fs.writeFileSync('tests/results/v6-health-search.json',JSON.stringify(checks,null,2)+'\n');
console.log(JSON.stringify({checks:checks.length,failed:checks.filter(x=>!x.passed)}));assert(checks.every(x=>x.passed));
