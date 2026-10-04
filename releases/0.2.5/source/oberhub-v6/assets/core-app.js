import {renderTrustedDocuments,evidenceLabels,evidenceTime} from '../../oberhub-core/assets/evidence.mjs?v=0.2.3';
import {normalise, createSearchIndex, searchIndex} from '../../oberhub-core/assets/search-engine.mjs?v=0.2.3';
export {normalise};
const indexes=new WeakMap();
export function findServices(data,query,lang='de',locality='all') {
 if(!indexes.has(data))indexes.set(data,createSearchIndex({...data,answers:[],faqs:[],contacts:[],organizations:[],places:[],events:[]}));
 return searchIndex(indexes.get(data),query,lang,{perPage:20,locality,types:['services']}).results.filter(x=>x.type==='services').map(x=>x.record);
}
export function navigationKinds(query){
 const q=normalise(query),patterns={
  cost:/kost|gebuhr|fee|cost|стоим|стоит|оплат|кошту|кошти|вартіс/,
  requirements:/voraussetz|unterlag|dokument|document|eligibil|requirement|документ|услов|умов/,
  deadline:/frist|bearbeitungszeit|deadline|duration|processing time|when|wann|срок|строк|тривалі|когда|коли/,
  online:/online|formul|digital|онлайн|онлаин|форм/,
  apply:/beantrag|anmeld|application|apply|proceed|gehe ich|vorgeh|оформ|подать|подати|заяв/,
  route:/zustand|kontakt|anlauf|stelle|authority|contact|office|служб|обрат|звер|установ/};
 const kinds=Object.entries(patterns).filter(([,pattern])=>pattern.test(q)).map(([kind])=>kind);
 return kinds.length?kinds:['overview'];
}
export function swissToday(now=new Date()){return new Intl.DateTimeFormat('en-CA',{timeZone:'Europe/Zurich',year:'numeric',month:'2-digit',day:'2-digit'}).format(now);}
function addDay(iso,n=1){const d=new Date(iso+'T12:00:00Z');d.setUTCDate(d.getUTCDate()+n);return d.toISOString().slice(0,10);}
export function nextWaste(record,locality,today=swissToday()){
 if(today<record.valid_from||today>record.valid_to)return null;
 const exact=(record.dates[locality]||[]).filter(x=>x>=today).sort()[0];if(exact)return exact;
 const weekday=record.weekday[locality];if(!weekday)return null;
 const date=new Date(today+'T12:00:00Z');let offset=(weekday-date.getUTCDay()+7)%7;
 // Day-of collection after 06:00 is already over; at date-only test time return that day.
 let next=addDay(today,offset);return next<=record.valid_to?next:null;
}
export function icsEscape(x){return String(x).replace(/\\/g,'\\\\').replace(/\r?\n/g,'\\n').replace(/;/g,'\\;').replace(/,/g,'\\,');}
export function makeICS(events,lang='de'){
 const lines=['BEGIN:VCALENDAR','VERSION:2.0','PRODID:-//Oberriet Hub//Calendar 0.1//DE','CALSCALE:GREGORIAN'];
 for(const e of events){lines.push('BEGIN:VEVENT','UID:'+icsEscape(e.id+'@oberriet-hub'),'DTSTAMP:'+new Date().toISOString().replace(/[-:]/g,'').replace(/\.\d{3}/,''),'DTSTART;VALUE=DATE:'+e.date.replaceAll('-',''),'DTEND;VALUE=DATE:'+addDay(e.end_date||e.date).replaceAll('-',''),'SUMMARY:'+icsEscape(e.title[lang]),'DESCRIPTION:'+icsEscape((e.description?.[lang]||'')+'\n'+e.source_url),'LOCATION:'+icsEscape(e.location||''),'URL:'+e.source_url,'END:VEVENT');}lines.push('END:VCALENDAR');
 // RFC5545 folding: at most 75 octets per physical line; do not split UTF-8 chars.
 return lines.map(line=>{let out='',part='';const enc=new TextEncoder();for(const c of line){if(enc.encode(part+c).length>74){out+=part+'\r\n ';part='';}part+=c;}return out+part;}).join('\r\n')+'\r\n';
}
const root=typeof document!=='undefined'?document.getElementById('oberhub-app'):null;
const startupLabels={de:{loading:'Aktuelle Informationen werden geladen…',failed:'Informationen konnten nicht geladen werden. Bitte laden Sie die Seite erneut.'},en:{loading:'Loading current information…',failed:'Could not load information. Please reload the page.'},ru:{loading:'Загружаем актуальные данные…',failed:'Не удалось загрузить данные. Обновите страницу.'},uk:{loading:'Завантажуємо актуальні дані…',failed:'Не вдалося завантажити дані. Оновіть сторінку.'}};
function startupStatus(message){for(const id of ['event-list','waste-list']){const target=document.getElementById(id);if(target){const p=document.createElement('p');p.className='quiet';p.setAttribute('role','status');p.textContent=message;target.replaceChildren(p);}}}
if(root){startupStatus((startupLabels[root.dataset.lang]||startupLabels.de).loading);boot().catch(()=>{startupStatus((startupLabels[root.dataset.lang]||startupLabels.de).failed);const target=document.getElementById('search-results');if(target){const p=document.createElement('p');p.textContent=(startupLabels[root.dataset.lang]||startupLabels.de).failed;target.append(p);}});}
async function boot(){
 const lang=root.dataset.lang,path=root.dataset.path,base=root.dataset.base,api=root.dataset.api;
 const [data,uis]=await Promise.all([fetch(api+'/index?view='+(path==='ask'?'router':'public')).then(r=>{if(!r.ok)throw Error('index');return r.json();}),fetch(root.dataset.assets+'../ui.json').then(r=>r.json())]);const u=uis[lang];
 const $=id=>document.getElementById(id);let locality=path.startsWith('places/')?path.split('/')[1]:'all';let selected=null,shownEvents=[];
 if($('locality'))$('locality').value=locality;
 const url=p=>base+lang+'/'+(p?p.replace(/^\/|\/$/g,'')+'/':'');
 const el=(tag,text,cls)=>{const n=document.createElement(tag);if(text!==undefined)n.textContent=text;if(cls)n.className=cls;return n;};
 const link=(text,href,cls)=>{const n=el('a',text,cls);if(/^https:\/\//.test(href)||href.startsWith(base)||href.startsWith('mailto:'))n.href=href;return n;};
 function metric(intent,event='search_success'){if(!data.services.some(s=>s.id===intent)&&intent!=='unknown')return;fetch(api+'/index?intent='+encodeURIComponent(intent)+'&event='+encodeURIComponent(event),{credentials:'omit',cache:'no-store'}).catch(()=>{});}
 const date=x=>new Intl.DateTimeFormat({de:'de-CH',en:'en-GB',ru:'ru-RU',uk:'uk-UA'}[lang],{day:'2-digit',month:'long',year:'numeric',timeZone:'Europe/Zurich'}).format(new Date(x+'T12:00:00Z'));
 function source(node,row){const box=el('div',undefined,'provenance');const evidence=row._evidence;box.append(link(u.source+' ↗',row.source_url));if(evidence){const labels=evidenceLabels[lang];box.append(el('span',labels.checked+': '+(evidenceTime(evidence.checked_at,lang)||labels.unknown)));if(evidence.content_reviewed_at)box.append(el('span',labels.reviewed+': '+(evidenceTime(evidence.content_reviewed_at,lang)||labels.unknown)));const confidence=['high','medium','low'].includes(evidence.confidence)?evidence.confidence:'low';box.append(el('span',labels[confidence],confidence==='low'?'warning':'quiet'));if(evidence.stale)box.append(el('span',labels.unconfirmed,'warning'));}else{box.append(el('span',u.checked+' '+(row.source_checked_at||'')));const registry=data.sources.find(s=>s.source_id===row.source_id);if(registry?.review_status!=='checked'||(registry&&addDay(row.source_checked_at,registry.ttl_days||30)<swissToday()))box.append(el('span',u.needsreview,'warning'));}node.append(box);}
 const text=v=>typeof v==='string'?v:(v?.[lang]||v?.de||v?.en||'');
 const labels={
 de:{found:'Gefunden',more:'Mehr anzeigen',expanded:'Erweitert auf',reason:'km, weil es in der Nähe wenige Optionen gibt.',types:{service:'Geprüfte Leistung',organization:'Organisation',place:'Ort',business:'Unternehmen',document:'Dokument',form:'Formular',discovery:'Webfund',event:'Veranstaltung',contact:'Kontakt'},trust:{verified:'Verifiziert',reviewed:'Geprüft','provider-site':'Anbieter-Website','public-map':'Öffentliche Karte',directory:'Verzeichnis',web:'Websuche'},actions:{website:'Website öffnen',map:'Karte öffnen',form:'Formular öffnen',details:'Details',source:'Quelle öffnen'},all:'Alle Typen',type:'Ergebnistyp',area:'Ort'},
 en:{found:'Found',more:'Show more',expanded:'Expanded to',reason:'km because few options were found nearby.',types:{service:'Verified service',organization:'Organization',place:'Place',business:'Business',document:'Document',form:'Form',discovery:'Web discovery',event:'Event',contact:'Contact'},trust:{verified:'Verified',reviewed:'Reviewed','provider-site':'Provider website','public-map':'Public map',directory:'Directory',web:'Web search'},actions:{website:'Open website',map:'Open map',form:'Open form',details:'Details',source:'Open source'},all:'All types',type:'Result type',area:'Locality'},
 ru:{found:'Найдено',more:'Показать ещё',expanded:'Расширено до',reason:'км, потому что рядом найдено мало вариантов.',types:{service:'Услуга',organization:'Организация',place:'Место',business:'Бизнес',document:'Документ',form:'Форма',discovery:'Web discovery',event:'Событие',contact:'Контакт'},trust:{verified:'Проверено',reviewed:'Проверенный источник','provider-site':'Сайт организации','public-map':'Публичная карта',directory:'Каталог',web:'Web-поиск'},actions:{website:'Открыть сайт',map:'Открыть карту',form:'Открыть форму',details:'Подробнее',source:'Открыть источник'},all:'Все типы',type:'Тип результата',area:'Населённый пункт'},
 uk:{found:'Знайдено',more:'Показати ще',expanded:'Розширено до',reason:'км, тому що поруч знайдено мало варіантів.',types:{service:'Послуга',organization:'Організація',place:'Місце',business:'Бізнес',document:'Документ',form:'Форма',discovery:'Web discovery',event:'Подія',contact:'Контакт'},trust:{verified:'Перевірено',reviewed:'Перевірене джерело','provider-site':'Сайт організації','public-map':'Публічна карта',directory:'Каталог',web:'Web-пошук'},actions:{website:'Відкрити сайт',map:'Відкрити карту',form:'Відкрити форму',details:'Докладніше',source:'Відкрити джерело'},all:'Усі типи',type:'Тип результату',area:'Населений пункт'}
 }[lang];
 function resultTarget(record){
  const supplied=record.target;
  const safe=v=>{try{const x=new URL(v,base);return x.protocol==='https:'&&!x.username&&!x.password&&!/\/services\/(?:web[:%]|osm[:%])/i.test(decodeURIComponent(x.pathname))?x.href:'';}catch{return '';}};
  if(supplied?.url){const href=safe(supplied.url);if(href)return {...supplied,url:href};}
  if(/^osm:(node|way|relation):\d+$/.test(record.id||'')){const [,type,id]=record.id.split(':');return {url:safe(record.website)||safe(record.official_url)||'https://www.openstreetmap.org/'+type+'/'+id,internal:false,action:record.website?'website':'map'};}
  if(record.discovery_level||/^(web|osm):/.test(record.id||''))return {url:safe(record.official_url)||safe(record.source_url),internal:false,action:'website'};
  if(data.services.some(r=>String(r.id)===String(record.id)))return {url:url('services/'+encodeURIComponent(record.id)),internal:true,action:'details'};
  return {url:safe(record.form_url)||safe(record.official_url)||safe(record.source_url),internal:false,action:record.form_url?'form':'website'};
 }
 function card(s,choose=false){
  const a=el('article',undefined,'service-card'),target=resultTarget(s),kind=s.result_kind||'service';
  a.dataset.resultId=s.id;a.append(el('div',labels.types[kind]||kind,'eyebrow'));
  const h=el('h3');h.append(target.url?link(text(s.title)||text(s.name),target.url):el('span',text(s.title)||text(s.name)));a.append(h);
  const summary=text(s.description_short).slice(0,300);if(summary)a.append(el('p',summary));
  const location=s.location_label||(s.locality!=='all'?s.locality:'')||s.authority||'';
  a.append(el('small',location+(Number.isFinite(s.distance_km)?' · '+s.distance_km+' km':'')));
  if(s.trust)a.append(el('div',labels.trust[s.trust]||s.trust,'quiet'));
  if(s.components?.length)a.append(el('p',s.components.join(' · ').slice(0,300),'quiet'));
  if(choose){const btn=el('button',u.compose,'button');btn.type='button';btn.addEventListener('click',()=>selectService(s));a.append(btn);}
  else if(target.url){const action=link(labels.actions[target.action]||labels.actions.source,target.url,'button secondary');if(!target.internal)action.rel='noopener noreferrer';a.append(action);}
  if(s.source_url){const box=el('div',undefined,'provenance');box.append(link(u.source+' ↗',s.source_url));a.append(box);}return a;
 }
 let searchSequence=0;
 const state={q:'',type:'',page:1,lang,locality};
 function remember(){const x=new URL(window.location.href);for(const k of ['q','lang','locality','type','page']){if(state[k]!==''&&state[k]!==undefined)x.searchParams.set(k,state[k]);else x.searchParams.delete(k);}history.replaceState(null,'',x);}
 function filters(){
  if($('result-type'))return;
  const row=el('div',undefined,'filters');const typeLabel=el('label',labels.type),select=el('select');select.id='result-type';
  for(const value of ['', 'services','organizations','places','documents']){const option=el('option',value?(labels.types[{services:'service',organizations:'organization',places:'place',documents:'document'}[value]]):labels.all);option.value=value;select.append(option);}select.value=state.type;typeLabel.append(select);row.append(typeLabel);
  select.addEventListener('change',()=>{state.type=select.value;results($('search-results'),$('query').value);});
  if(!$('locality')){const areaLabel=el('label',labels.area),area=el('select');area.id='search-locality';for(const value of ['all','oberriet','montlingen','kriessern','eichenwies','kobelwald']){const option=el('option',value==='all'?u.all:value[0].toUpperCase()+value.slice(1));option.value=value;area.append(option);}area.value=locality;areaLabel.append(area);row.append(areaLabel);area.addEventListener('change',()=>{locality=area.value;results($('search-results'),$('query').value);});}
  $('search-form').after(row);
 }
 async function results(target,q,choose=false,initialPage=1){
  const sequence=++searchSequence;target.replaceChildren();
  if(choose){const matches=findServices(data,q,lang,locality);const cards=el('div',undefined,'cards');matches.forEach(s=>cards.append(card(s,true)));target.append(cards);return;}
  state.q=q.slice(0,300);state.page=initialPage;state.locality=locality;remember();filters();
  const count=el('p','','quiet');count.setAttribute('role','status');const geo=el('p','','quiet'),cards=el('div',undefined,'cards'),more=el('button',labels.more,'button secondary');more.type='button';more.hidden=true;
  target.append(count,geo,cards,more);const seen=new Set();let activeLive=false;
  async function load(page=1,live=false){
   more.disabled=true;
   try{const response=await fetch(api+'/search',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({q:state.q,lang,per_page:20,locality,type:state.type,page,live}),credentials:'omit',cache:'no-store'});if(!response.ok)throw Error('search');const result=await response.json();
    if(sequence!==searchSequence)return;
    if(page===1){cards.replaceChildren();seen.clear();}
    for(const hit of result.results||[]){const s={...hit.record,_evidence:hit.evidence};if(seen.has(s.id))continue;seen.add(s.id);cards.append(card(s));}
    count.textContent=labels.found+': '+result.total;geo.textContent=result.geo_fallback?.expanded?labels.expanded+' '+result.geo_fallback.selected_tier+' '+labels.reason:'Oberriet · Rheintal';
    state.page=page;remember();activeLive=live;more.hidden=page>=result.pages;
    if(!result.total)count.textContent=u.noresults;
    // AI reuses the same locality/type retrieval context; a new query clears the previous answer.
    if(page===initialPage)target.dispatchEvent(new CustomEvent('oberhub:results',{detail:{question:state.q,lang,locality,type:state.type,records:(result.results||[]).map(h=>h.record)}}));
    // Deliver available local cards first, then try a bounded fallback only when genuinely needed.
    if(page===1&&!live&&result.total<3&&!result.intent?.official)void load(1,true);
   }catch{if(sequence!==searchSequence)return;count.textContent=u.noresults;const local=findServices(data,q,lang,locality);if(!cards.children.length)local.forEach(r=>cards.append(card(r)));}
   finally{if(sequence===searchSequence)more.disabled=false;}
  }
  more.addEventListener('click',()=>load(state.page+1,activeLive));await load(initialPage);
 }
 if($('search-form')){const params=new URL(window.location.href).searchParams;state.type=params.get('type')||'';locality=params.get('locality')||locality;if($('locality'))$('locality').value=locality;const initial=new URL(window.location.href).searchParams.get('q');if(initial){$('query').value=initial.slice(0,300);results($('search-results'),$('query').value,false,Math.max(1,Number(params.get('page'))||1));}let timer;const run=()=>results($('search-results'),$('query').value);$('search-form').addEventListener('submit',e=>{e.preventDefault();run();});$('query').addEventListener('input',()=>{clearTimeout(timer);timer=setTimeout(()=>{if($('query').value.trim().length>=2)run();else $('search-results').replaceChildren();},250);});}
 if($('topic'))$('topic').addEventListener('change',()=>{window.location.href=$('topic').value==='all'?url('services'):url('topics/'+$('topic').value);});
 function wasteRecords(){return path===''?data.waste.filter(w=>['kehricht','karton','papier'].includes(w.id)):data.waste;}
 function renderWaste(){if(!$('waste-list'))return;const target=$('waste-list');target.replaceChildren();if(locality==='all'&&path!==''){target.append(el('p',u.locality));}
 const loc=locality==='all'?'oberriet':locality;const today=swissToday();for(const w of wasteRecords()){const n=el('article',undefined,path==='waste'?'service-card':'event-row');n.append(el('h3',w.title[lang]));let next=nextWaste(w,loc,today);
 // Suppress same-day collection after 06:00 Europe/Zurich.
 if(next===today&&w.weekday[loc]){const hour=Number(new Intl.DateTimeFormat('en-GB',{timeZone:'Europe/Zurich',hour:'2-digit',hourCycle:'h23'}).format(new Date()));if(hour>=6)next=nextWaste(w,loc,addDay(today));}
 if(today>w.valid_to)n.append(el('p',u.expired,'warning'));else if(next)n.append(el('span',date(next),'date-pill'));else n.append(el('p',u.noconfirmeddate,'quiet'));
 n.append(el('p',w.instruction[lang]));if(loc==='kobelwald')n.append(el('p',u.routeconfirm,'warning'));source(n,w);target.append(n);}}
 function calendarEvents(){let events=data.events.filter(e=>(e.end_date||e.date)>=swissToday()&&(locality==='all'||e.locality===locality||e.locality==='all'));const today=swissToday();let end=null;const period=$('period')?.value||'future';if(period==='today')end=today;if(period==='week'){const day=new Date(today+'T12:00Z').getUTCDay();end=addDay(today,(7-day)%7);}if(period==='month')end=today.slice(0,7)+'-31';
 const type=$('event-type')?.value||'all';if(type==='waste'||(path==='calendar'&&type==='all')){for(const loc of locality==='all'?['oberriet','montlingen','kriessern','eichenwies','kobelwald']: [locality]){for(const w of data.waste.filter(x=>['kehricht','karton','papier'].includes(x.id))){let next=nextWaste(w,loc,today),count=0;while(next && (!end||next<=end) && count++<60){events.push({id:w.id+'-'+loc+'-'+next,title:w.title,date:next,end_date:next,description:w.instruction,source_url:w.source_url,source_checked_at:w.source_checked_at,source_id:w.source_id,locality:loc,type:'waste',location:loc});next=nextWaste(w,loc,addDay(next));}}}}
 return events.filter(e=>(!end||e.date<=end)&&(type==='all'||e.type===type)).sort((a,b)=>a.date.localeCompare(b.date));}
 function renderEvents(){if(!$('event-list'))return;const target=$('event-list');target.replaceChildren();shownEvents=calendarEvents();const shown=path===''?shownEvents.slice(0,3):shownEvents;if(!shown.length){target.append(el('p',u.noevents));return;}for(const e of shown){const n=el('article',undefined,'event-row');n.append(el('div',date(e.date)+(e.end_date&&e.end_date!==e.date?' — '+date(e.end_date):''),'event-date'),el('h3',e.title[lang]),el('p',e.location),el('p',e.description[lang]));source(n,e);target.append(n);}}
 const refresh=()=>{if($('place-link'))$('place-link').href=url('places/'+(locality==='all'?'oberriet':locality));renderWaste();renderEvents();};
 if($('locality'))$('locality').addEventListener('change',()=>{locality=$('locality').value;refresh();if($('query')?.value.trim())results($('search-results'),$('query').value);});for(const id of ['period','event-type'])if($(id))$(id).addEventListener('change',()=>{renderEvents();fetch(api+'/calendar?locality='+encodeURIComponent(locality),{credentials:'omit'}).catch(()=>{});});refresh();
 if($('ics'))$('ics').addEventListener('click',()=>{const blob=new Blob([makeICS(shownEvents,lang)],{type:'text/calendar;charset=utf-8'});const href=URL.createObjectURL(blob);const a=el('a');a.href=href;a.download='oberriet-hub.ics';a.click();setTimeout(()=>URL.revokeObjectURL(href),1000);});
 function draft(){if(!selected)return;const c=data.contacts.find(c=>c.id===selected.contact)||{title:selected.authority||u.contact,email:'',phone:''};const subject='Anfrage: '+selected.title.de;const name=$('resident-name').value.trim();const location=$('location').value.trim();let desc=$('description').value.trim();
 if(lang!=='de' && selected.id!=='lighting' && desc)desc='Beschreibung in der Originalsprache (bitte bei Bedarf übersetzen):\n'+desc;
 if(selected.id==='lighting'&&/фонар|ліхтар|streetlight/i.test(desc)&&desc.length<120)desc='Die Strassenbeleuchtung am unten genannten Standort funktioniert nicht. Bitte prüfen Sie die Störung.';
 const body='Guten Tag\n\nich habe ein Anliegen zum Thema «'+selected.title.de+'».\n\n'+(location?'Standort: '+location+'\n\n':'')+(desc?desc+'\n\n':'')+'Bitte teilen Sie mir mit, ob Sie dafür zuständig sind und welche nächsten Schritte erforderlich sind. Falls eine andere Stelle zuständig ist, bitte ich um deren Kontaktdaten.\n\nVielen Dank.\nFreundliche Grüsse\n'+(name||'[Name]');$('draft').value='Betreff: '+subject+'\n\n'+body;$('mailto').href='mailto:'+encodeURIComponent(c.email)+'?subject='+encodeURIComponent(subject)+'&body='+encodeURIComponent(body);$('recipient').textContent=u.recipient+': '+c.title+' · '+(c.email||c.phone);if(!c.email)$('mailto').hidden=true;else $('mailto').hidden=false;
 }
 function selectService(s){selected=s;$('composer').hidden=false;$('description').value=$('problem').value;draft();metric(s.id,'email_generated');$('composer').scrollIntoView({behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'instant':'smooth',block:'start'});}
 if($('classify')){$('classify').addEventListener('click',()=>results($('router-results'),$('problem').value,true));['resident-name','location','description'].forEach(id=>$(id).addEventListener('input',draft));const id=locationHash();const s=data.services.find(s=>s.id===id);if(s)selectService(s);}
 if($('copy'))$('copy').addEventListener('click',async()=>{try{await navigator.clipboard.writeText($('draft').value);$('copy-status').textContent=u.copied;}catch{$('draft').focus();$('draft').select();$('copy-status').textContent=u.copyfailed;}});
 if($('clear'))$('clear').addEventListener('click',()=>{['resident-name','location','description','problem','draft'].forEach(id=>$(id).value='');$('composer').hidden=true;$('router-results').replaceChildren();selected=null;$('problem').focus();});
 document.querySelectorAll('.languages a').forEach(a=>a.addEventListener('click',()=>{fetch(api+'/index?event=language_switch&dimension='+a.lang,{credentials:'omit',keepalive:true}).catch(()=>{});}));
 document.querySelectorAll('[data-official]').forEach(a=>a.addEventListener('click',()=>metric(a.dataset.official,'official_link_click')));
 // No localStorage/sessionStorage, no user text in analytics, no form submission.
}
function locationHash(){if(typeof window==='undefined')return '';try{return decodeURIComponent(window.location.hash.slice(1));}catch{return '';}}

