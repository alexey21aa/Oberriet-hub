import concepts from './query-concepts.mjs';
const conceptCache=new Map();
const conceptMap=new Map(Object.entries(concepts).flatMap(([key,aliases])=>aliases.map(alias=>[normalise(alias),key])));
/** Local, deterministic search. No runtime network or paid provider dependency. */
export function normalise(text) {
 return String(text??'').toLocaleLowerCase().replace(/[ä]/g,'a').replace(/[ö]/g,'o').replace(/[ü]/g,'u').replace(/(?<![\p{L}\p{N}])доя(?![\p{L}\p{N}])/gu,'для').replace(/ß/g,'ss').replace(/ё/g,'е').replace(/ґ/g,'г').normalize('NFKD').replace(/[\u0300-\u036f]/g,'').replace(/[^\p{L}\p{N}\s]/gu,' ').replace(/\s+/g,' ').trim();
}
const stop=new Set('the a an of to in and for is i my do how where can me der die das und ein eine im in am mit von zu ich mein meine wo wie ist was на в и у а по за для з та що як де не мне мой моя це of'.split(' '));
export function tokens(text){return [...new Set(normalise(text).split(' ').filter(w=>w.length>1&&!stop.has(w)).map(w=>{if(conceptMap.has(w))return conceptMap.get(w);if(conceptCache.has(w))return conceptCache.get(w);if([...w].length<4)return w;const matches=new Set([...conceptMap].filter(([a])=>distance(w,a)<=1).map(([,c])=>c));const result=matches.size===1?[...matches][0]:w;if(conceptCache.size<10000)conceptCache.set(w,result);return result;}))];}
function values(v){if(Array.isArray(v))return v.flatMap(values);if(v&&typeof v==='object')return Object.values(v).flatMap(values);return typeof v==='string'?[v]:[];}
function signatures(word){const a=[word];if(word.length>=4&&word.length<=45)for(let i=0;i<word.length;i++)a.push(word.slice(0,i)+word.slice(i+1));return a;}
export function distance(a,b){if(a===b)return 0;if(Math.abs(a.length-b.length)>1)return 2;const aa=[...a],bb=[...b];let prev=bb.map((_,i)=>i+1);prev.unshift(0);let before=null;for(let i=1;i<=aa.length;i++){let row=[i];for(let j=1;j<=bb.length;j++){row[j]=Math.min(row[j-1]+1,prev[j]+1,prev[j-1]+(aa[i-1]===bb[j-1]?0:1));if(i>1&&j>1&&aa[i-1]===bb[j-2]&&aa[i-2]===bb[j-1])row[j]=Math.min(row[j],before[j-2]+1);}before=prev;prev=row;}return prev[bb.length];}
export function createSearchIndex(data){
 const records=new Map(),terms=new Map(),deletions=new Map(),phrases=new Map(),intentMap=new Map();
 for(const intent of data.intents||[]){const id=String(intent.service_id||intent.target_id||intent.id);intentMap.set(id,[...(intentMap.get(id)||[]),...values(intent.phrases||intent.aliases||intent.question)]);}
 for(const alias of data.aliases||[]){const id=String(alias.service_id||'');intentMap.set(id,[...(intentMap.get(id)||[]),...values(alias.phrase)]);}
 for(const type of ['services','guides','faqs','answers','contacts','organizations','places','events'])for(const record of data[type]||[]){
  const key=type+':'+record.id;records.set(key,{record,type});const fields=[[record.title||record.name,8],[record.synonyms||record.aliases,10],[intentMap.get(String(record.id)),12],[record.question,10],[record.search_concepts,12],[record.keywords,3],[record.description_short,2],[record.answer,2],[record.description_full,1]];
  const p=[];for(const [field,weight] of fields)for(const text of values(field)){const phrase=normalise(text);if(weight>=8&&phrase)p.push({text:phrase,weight});for(const token of tokens(text)){if(!terms.has(token))terms.set(token,new Map());const postings=terms.get(token);postings.set(key,Math.max(postings.get(key)||0,weight));}}const distinct=new Map();for(const item of p){if(!distinct.has(item.text)||distinct.get(item.text).weight<item.weight)distinct.set(item.text,item);}phrases.set(key,[...distinct.values()]);
 }
 for(const term of terms.keys())for(const sig of signatures(term)){if(!deletions.has(sig))deletions.set(sig,new Set());deletions.get(sig).add(term);}
 return {records,terms,deletions,phrases};
}
export function searchIndex(index,query,lang='de',options={}){
 const q=normalise(query).slice(0,300),rawTokens=tokens(q),intentTokens=rawTokens.filter(t=>!['oberriet','montlingen','kriessern','eichenwies','kobelwald','altstatten','rheintal','heerbrugg','widnau','buchs','rebstein','marbach','ruthi','balgach','grabs','st','gallen','sankt','stgallen'].includes(t)),queryTokens=intentTokens.length?intentTokens:rawTokens,page=Math.max(1,Math.min(1000,Number(options.page)||1)),perPage=Math.max(1,Math.min(50,Number(options.perPage)||10));
 if(q.length<2||!queryTokens.length)return {results:[],total:0,page,per_page:perPage,pages:0};
 const scores=new Map(),hits=new Map();
 for(const word of queryTokens){const candidates=new Map();if(index.terms.has(word))candidates.set(word,1);if(word.length>=4){for(const sig of signatures(word))for(const term of index.deletions.get(sig)||[])if(distance(word,term)<=1)candidates.set(term,term===word?1:.65);}
  if(!candidates.size&&word.length>=3)for(const term of index.terms.keys())if(term.startsWith(word))candidates.set(term,.7);
  const wordBest=new Map();for(const [term,factor]of candidates)for(const [key,weight]of index.terms.get(term)||[])wordBest.set(key,Math.max(wordBest.get(key)||0,weight*factor));
  for(const [key,score]of wordBest){scores.set(key,(scores.get(key)||0)+score);hits.set(key,(hits.get(key)||0)+1);}
 }
 const matched=[];for(const [key,score]of scores){const entry=index.records.get(key);if(options.types&&!options.types.includes(entry.type))continue;
  if(queryTokens.some(x=>['sport','dance','indoor'].includes(x))){const labels=entry.record.search_concepts||[];if(queryTokens.filter(x=>['sport','dance','indoor','children'].includes(x)).some(x=>!labels.includes(x)))continue;if(queryTokens.includes('women')&&!labels.some(x=>['women','adults'].includes(x)))continue;}
  const coverage=hits.get(key)/queryTokens.length;if(coverage<.5)continue;let ranked=score*coverage+40*coverage*coverage,phraseBonus=0;for(const phrase of index.phrases.get(key)||[]){if(phrase.text===q)phraseBonus=Math.max(phraseBonus,100+phrase.weight+(phrase.weight>=10?30:0));else if(phrase.text.length>=4&&q.includes(phrase.text))phraseBonus=Math.max(phraseBonus,30+phrase.weight);}
  const titleTokens=tokens(typeof entry.record.title==='string'?entry.record.title:(entry.record.title?.[lang]||entry.record.name?.[lang]||''));ranked+=30*queryTokens.filter(t=>titleTokens.includes(t)).length/queryTokens.length;ranked+=phraseBonus;const nearby={oberriet:10,montlingen:9,kriessern:9,eichenwies:9,kobelwald:9,altstatten:6,rheintal:3,grabs:1};if(queryTokens.some(x=>['sport','dance','indoor'].includes(x)))ranked+=nearby[entry.record.locality]||0;if(['page','page-subscenario','primary-provider-page'].includes(entry.record.verification_scope))ranked+=50;if(normalise(typeof entry.record.title==='string'?entry.record.title:(entry.record.title?.[lang]||entry.record.name?.[lang]))===q)ranked+=15;if(entry.record.status==='checked')ranked+=.5;if(['A','B'].includes(entry.record.source_trust_level))ranked+=.5;if(entry.record.verification_scope==='routing-metadata')ranked-=.2;const checked=Date.parse(entry.record.source_checked_at);if(Number.isFinite(checked)&&checked>=Date.now()-90*86400000)ranked+=.3;const place=normalise(entry.record.locality);if(place&&place!=='all'&&(place===normalise(options.locality)||q.includes(place)))ranked+=25;matched.push({...entry,score:Math.round(ranked*100)/100});
 }matched.sort((a,b)=>b.score-a.score||String(a.record.id).localeCompare(String(b.record.id)));const seen=new Set(),unique=matched.filter(x=>{const family=String(x.record.service_id||x.record.id);if(seen.has(family))return false;seen.add(family);return true;});const total=unique.length;
 return {results:unique.slice((page-1)*perPage,page*perPage),total,page,per_page:perPage,pages:Math.ceil(total/perPage)};
}
