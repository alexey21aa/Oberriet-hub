import fs from 'node:fs';
import assert from 'node:assert/strict';
import {createSearchIndex,searchIndex} from '../wp-content/plugins/oberhub-core/assets/search-engine.mjs';
const data=JSON.parse(fs.readFileSync('data/seed.json'));
for(const batch of ['activities','life-services','community-services','support-services','health-services'])data.services.push(...JSON.parse(fs.readFileSync(`data/v6-${batch}-delta.json`)).services);
const patches=JSON.parse(fs.readFileSync('data/v6-family-provider-enrichment-patches.json'));
const family=patches.map(p=>({id:p.canonical_id,title:{de:p.post_id===3490?'Frauenhaus St.Gallen':p.post_id===3543?'Pro Infirmis St.Gallen':'Mütterberatung Oberriet'},...p.fields}));
data.services.push(...family);
// Reproduce broad directory/template audience matches without specific evidence.
for(let i=0;i<2000;i++)data.services.push({id:'unrelated-women-'+i,title:{ru:'Общая услуга для женщин',en:'General women service'},description_short:{ru:'Административные услуги и формы'},description_full:{ru:'Бойлерплейт: рак, стома, убежище, инвалидность'},topic:'administration'});
const index=createSearchIndex(data);const shelter=patches.find(x=>x.post_id===3490).canonical_id;
const queries=['убежище для женщин','убежише доя женщин','shelter for women','Frauenhaus','притулок для жінок'];
const checks=queries.map(q=>{const result=searchIndex(index,q,'ru',{perPage:50});return{q,ids:result.results.map(x=>x.record.id),total:result.total,passed:result.results.some(x=>x.record.id===shelter)&&result.results.every(x=>!x.record.id.startsWith('unrelated-women-'))};});
for(const q of ['palliative women','stoma women','cancer women','инвалидность женщин','violence women']){const result=searchIndex(index,q,'ru',{perPage:50});checks.push({q,passed:result.results.every(x=>!x.record.id.startsWith('unrelated-women-'))});}
// Separate legitimate asylum topic is retained for a specific asylum question.
const asylum={id:'political-asylum',title:{ru:'Политическое убежище'},description_short:{ru:'Подача заявления о политическом убежище'}};
const political=createSearchIndex({services:[asylum,...family]});
checks.push({q:'политическое убежище',passed:searchIndex(political,'политическое убежище','ru').results[0]?.record.id===asylum.id});
for(const q of ['Спорт для детей','Спорт доя детей','Танцы для женщин','танци доя женщин','Развлекательные центры для детей']){const result=searchIndex(index,q,'ru',{perPage:50});checks.push({q,passed:result.results.length>0&&result.results.every(x=>!x.record.id.startsWith('unrelated-women-')&&!family.some(f=>f.id===x.record.id))});}
fs.writeFileSync('tests/results/v6-specific-intents.json',JSON.stringify(checks,null,2)+'\n');console.log(JSON.stringify({checks:checks.length,failed:checks.filter(x=>!x.passed)}));assert(checks.every(x=>x.passed));
