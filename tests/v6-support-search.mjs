import fs from 'node:fs';
import assert from 'node:assert/strict';
import {createSearchIndex,searchIndex} from '../wp-content/plugins/oberhub-core/assets/search-engine.mjs';
const data=JSON.parse(fs.readFileSync('data/seed.json'));
const support=JSON.parse(fs.readFileSync('data/v6-support-services-delta.json'));
for(const name of ['activities','life-services','community-services'])data.services.push(...JSON.parse(fs.readFileSync('data/v6-'+name+'-delta.json')).services);
data.services.push(...support.services);
const index=createSearchIndex(data);
const cases=support.services.flatMap(r=>Object.entries(r.title).map(([lang,q])=>({lang,q,id:r.id})));
for(const [q,slug] of [['уход на дому Oberriet','spitex-care'],['юридическая консультация Rheintal','legal-advice'],['помощь пострадавшим от насилия','victim-advice'],['автобусы Oberdorf','bus-oberdorf'],['схема автобусов Rheintal','bus-maps']])cases.push({lang:'ru',q,id:'v6-support-'+slug});
const checks=cases.map(({lang,q,id})=>{const ids=searchIndex(index,q,lang,{perPage:5}).results.map(x=>x.record.id);return{lang,q,expected:id,ids,passed:ids.includes(id)}});
// Support services must not displace sport, dance or indoor centres.
for(const q of ['Развлекательные центры для детей','Спорт доя детей','танци доя женщин']){const hits=searchIndex(index,q,'ru',{perPage:50}).results;checks.push({q,passed:hits.length>0&&hits.every(h=>!h.record.id.startsWith('v6-support-'))});}
fs.writeFileSync('tests/results/v6-support-search.json',JSON.stringify(checks,null,2)+'\n');
console.log(JSON.stringify({checks:checks.length,failed:checks.filter(x=>!x.passed)}));assert(checks.every(x=>x.passed));
