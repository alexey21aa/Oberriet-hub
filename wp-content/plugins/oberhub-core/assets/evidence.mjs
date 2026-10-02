/** Rendering contract for external document evidence. Content remains text, never HTML. */
export const evidenceLabels={
 de:{documents:'Treffer aus vertrauenswürdigen Quellen',original:'Auszug in der Originalsprache',checked:'Technisch geprüft',reviewed:'Inhaltlich geprüft',unconfirmed:'Aktuelle Bestätigung fehlt',high:'Hohe Verlässlichkeit',medium:'Quelle prüfen',low:'Bestätigung erforderlich',source:'Originalquelle öffnen',unknown:'Zeitpunkt nicht bekannt'},
 en:{documents:'Matches from trusted sources',original:'Excerpt in the original language',checked:'Source fetched',reviewed:'Content reviewed',unconfirmed:'Current confirmation is missing',high:'High confidence',medium:'Review the source',low:'Confirmation required',source:'Open original source',unknown:'Time unknown'},
 ru:{documents:'Найдено в доверенных источниках',original:'Выдержка на языке оригинала',checked:'Источник проверен технически',reviewed:'Содержание проверено',unconfirmed:'Нет актуального подтверждения',high:'Высокая уверенность',medium:'Уточните в источнике',low:'Требуется подтверждение',source:'Открыть первоисточник',unknown:'Время неизвестно'},
 uk:{documents:'Знайдено в довірених джерелах',original:'Уривок мовою оригіналу',checked:'Джерело перевірено технічно',reviewed:'Зміст перевірено',unconfirmed:'Немає актуального підтвердження',high:'Висока впевненість',medium:'Уточніть у джерелі',low:'Потрібне підтвердження',source:'Відкрити першоджерело',unknown:'Час невідомий'}
};
export function externalURL(value){try{const u=new URL(String(value));return u.protocol==='https:'&&!u.username&&!u.password?u.href:null;}catch{return null;}}
function localized(value,lang){return typeof value==='string'?value:(value?.[lang]||value?.de||'');}
export function evidenceTime(value,lang='de'){if(!value)return null;const d=new Date(value);if(!Number.isFinite(d.getTime()))return null;return new Intl.DateTimeFormat({de:'de-CH',en:'en-GB',ru:'ru-RU',uk:'uk-UA'}[lang]||'de-CH',{dateStyle:'medium',timeStyle:'short',timeZone:'Europe/Zurich'}).format(d);}
export function documentModel(hit,lang='de'){
 const record=hit?.record||{},evidence=hit?.evidence||record._evidence||{};
 const href=externalURL(record.source_url||record.official_url);if(!href||hit?.type!=='documents')return null;
 const labels=evidenceLabels[lang]||evidenceLabels.de;const confidence=['high','medium','low'].includes(evidence.confidence)?evidence.confidence:'low';
 return {title:String(localized(record.title,record.source_language||lang)),snippet:String(localized(record.description_short,record.source_language||lang)),href,language:String(record.source_language||'de').toUpperCase(),originalLabel:labels.original,confidence,confidenceLabel:labels[confidence],checkedLabel:labels.checked,checked:evidenceTime(evidence.checked_at||record.checked_at_utc||record.source_checked_at,lang)||labels.unknown,stale:evidence.stale!==false,staleLabel:labels.unconfirmed,sourceLabel:labels.source};
}
export function renderTrustedDocuments(hits,lang='de',doc=globalThis.document){
 const models=hits.map(hit=>documentModel(hit,lang)).filter(Boolean);if(!models.length)return null;
 const el=(tag,text,cls)=>{const n=doc.createElement(tag);if(text!==undefined)n.textContent=text;if(cls)n.className=cls;return n;};
 const section=el('section',undefined,'trusted-documents');section.setAttribute('aria-label',(evidenceLabels[lang]||evidenceLabels.de).documents);section.append(el('h2',(evidenceLabels[lang]||evidenceLabels.de).documents));
 const cards=el('div',undefined,'cards');for(const model of models){const article=el('article',undefined,'service-card trusted-document');const h=el('h3');const title=el('a',model.title);title.href=model.href;h.append(title);article.append(h,el('small',model.originalLabel+' · '+model.language),el('p',model.snippet));
  const box=el('div',undefined,'provenance');const link=el('a',model.sourceLabel+' ↗');link.href=model.href;box.append(link,el('span',model.checkedLabel+': '+model.checked),el('span',model.confidenceLabel,model.confidence==='low'?'warning':'quiet'));if(model.stale)box.append(el('span',model.staleLabel,'warning'));article.append(box);cards.append(article);
 }section.append(cards);return section;
}
