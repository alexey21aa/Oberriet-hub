import fs from 'node:fs';
import assert from 'node:assert/strict';
import {createSearchIndex,searchIndex} from '../wp-content/plugins/oberhub-core/assets/search-engine.mjs';
const base=JSON.parse(fs.readFileSync('data/seed.json'));
const life=JSON.parse(fs.readFileSync('data/v6-life-services-delta.json'));
const activity=JSON.parse(fs.readFileSync('data/v6-activities-delta.json'));
const index=createSearchIndex({...base,services:[...base.services,...activity.services,...life.services]});
const cases=[
['женские встречи','v6-life-rheintal-women'],['women meetings','v6-life-rheintal-women'],['Frauentreffs Rheintal','v6-life-rheintal-women'],['жіночі зустрічі','v6-life-rheintal-women'],
['мужские встречи','v6-life-rheintal-men'],['men meetings','v6-life-rheintal-men'],
['помощь с формами Oberriet','v6-life-oberriet-help'],['forms help Oberriet','v6-life-oberriet-help'],['допомога з формами Oberriet','v6-life-oberriet-help'],
['семейное чтение','v6-life-family-stories'],['family storytelling','v6-life-family-stories'],['сімейне читання','v6-life-family-stories'],
['курсы немецкого','v6-life-german-courses'],['German courses','v6-life-german-courses'],['Deutschkurse','v6-life-german-courses'],['курси німецької','v6-life-german-courses'],
['KulturLegi заявление','v6-life-kulturlegi-apply'],['KulturLegi application','v6-life-kulturlegi-apply'],['KulturLegi заява','v6-life-kulturlegi-apply'],
['субсидия медицинской страховки','v6-life-ipv-apply'],['health insurance subsidy','v6-life-ipv-apply'],['Prämienverbilligung Anmeldung','v6-life-ipv-apply'],['субсидія медичного страхування','v6-life-ipv-apply'],
['налоговые формы','v6-life-tax-forms'],['tax forms','v6-life-tax-forms'],['Steuerformulare','v6-life-tax-forms'],['податкові форми','v6-life-tax-forms'],
['продление срока декларации','v6-life-tax-extension'],['tax deadline extension','v6-life-tax-extension'],['Fristverlängerung Steuererklärung','v6-life-tax-extension'],['продовження строку декларації','v6-life-tax-extension'],
];
const checks=cases.map(([q,id])=>{const hits=searchIndex(index,q,'ru',{perPage:5}).results;return{q,expected:id,ids:hits.map(x=>x.record.id),passed:hits.some(x=>x.record.id===id)}});
fs.writeFileSync('tests/results/v6-life-search.json',JSON.stringify(checks,null,2)+'\n');console.log(JSON.stringify({checks:checks.length,failed:checks.filter(x=>!x.passed)}));assert(checks.every(x=>x.passed));
