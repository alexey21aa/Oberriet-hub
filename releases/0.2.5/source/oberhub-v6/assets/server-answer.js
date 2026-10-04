/* Server synthesis follows completed retrieval. No DOM link rewriting. */
(()=>{
 const root=document.getElementById('oberhub-app'),target=document.getElementById('search-results'),input=document.getElementById('query');if(!root||!target||!input)return;
 const lang=['de','en','ru','uk'].includes(root.dataset.lang)?root.dataset.lang:'de';
 const labels={de:['Antwort mit Quellen','KI-Antwort mit Quellen','Quellen'],en:['Answer with sources','AI answer with sources','Sources'],ru:['Ответ по источникам','Ответ ИИ по источникам','Источники'],uk:['Відповідь за джерелами','Відповідь ШІ за джерелами','Джерела']}[lang];
 let handled='',sequence=0,controller,timer;
 function clear(){sequence++;controller?.abort();handled='';target.querySelector('[data-v6-answer]')?.remove();}
 input.addEventListener('input',clear);
 target.addEventListener('oberhub:results',event=>{
  const detail=event.detail,key=JSON.stringify([detail.question,detail.locality,detail.type,(detail.records||[]).slice(0,7).map(r=>r.id)]);
  if(key===handled||!detail.question?.trim()||!detail.records?.length)return;
  clearTimeout(timer);timer=setTimeout(()=>run(detail,key),250);
 });
 async function run(detail,key){
  if(key===handled||input.value.trim().slice(0,300)!==detail.question.trim())return;handled=key;
  const request=++sequence;controller?.abort();controller=new AbortController();const expiry=setTimeout(()=>controller.abort(),22000);
  try{
   const response=await fetch(root.dataset.api.replace(/\/$/,'')+'/answer',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({question:detail.question,lang,locality:detail.locality,type:detail.type}),credentials:'omit',cache:'no-store',signal:controller.signal});if(!response.ok)return;
   const result=await response.json();if(request!==sequence||input.value.trim().slice(0,300)!==detail.question.trim())return;
   if(typeof result.answer!=='string'||!result.answer.trim()||!['evidence-pack','curated-answer-pack','server-ai'].includes(result.mode))return;
   if(result.mode==='server-ai'&&result.generative_available!==true)return;
   target.querySelector('[data-v6-answer]')?.remove();
   const section=document.createElement('section');section.dataset.v6Answer='';section.className='panel server-answer';section.setAttribute('aria-live','polite');
   const h=document.createElement('h2');h.textContent=labels[result.mode==='server-ai'?1:0];const p=document.createElement('p');p.textContent=result.answer.slice(0,8000);p.style.whiteSpace='pre-wrap';section.append(h,p);
   const list=document.createElement('ul');for(const source of [...new Set(result.sources||[])].slice(0,7)){try{const u=new URL(source);if(u.protocol!=='https:'||u.username||u.password)continue;const li=document.createElement('li'),a=document.createElement('a');a.href=u.href;a.textContent=u.hostname;a.rel='noopener noreferrer';li.append(a);list.append(li);}catch{}}
   if(list.children.length){const h=document.createElement('strong');h.textContent=labels[2];section.append(h,list);}target.prepend(section);
  }catch{/* Search cards and their deterministic summaries remain usable. */}finally{clearTimeout(expiry);}
 }
})();
