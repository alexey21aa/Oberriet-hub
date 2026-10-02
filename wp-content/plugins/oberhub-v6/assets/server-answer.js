/* Same server answer for every device. No local model or client context input. */
(()=>{
 const root=document.getElementById('oberhub-app');
 if(!root||root.dataset.path!=='search')return;
 const target=document.getElementById('search-results'),input=document.getElementById('query');
 if(!target||!input)return;
 const lang=['de','en','ru','uk'].includes(root.dataset.lang)?root.dataset.lang:'de';
 const labels={
  de:['Antwort mit Quellen','KI-Antwort mit Quellen','Quellen','Serverantwort derzeit nicht verfügbar. Die gefundenen Angebote stehen unten.'],
  en:['Answer with sources','AI answer with sources','Sources','Server answer is temporarily unavailable. The matching options are below.'],
  ru:['Ответ по источникам','Ответ ИИ по источникам','Источники','Серверный ответ временно недоступен. Найденные варианты доступны ниже.'],
  uk:['Відповідь за джерелами','Відповідь ШІ за джерелами','Джерела','Серверна відповідь тимчасово недоступна. Знайдені варіанти доступні нижче.']
 }[lang];
 let handled='',sequence=0,timer,controller;
 function clear(){sequence++;controller?.abort();handled='';target.querySelector('[data-v6-answer]')?.remove();}
 function schedule(){clearTimeout(timer);timer=setTimeout(run,350);}
 async function run(){
  const question=input.value.trim().slice(0,300),panel=target.querySelector('.answer-panel');
  if(question.length<2||question===handled||!panel)return;
  // A newer core can already render this response itself.
  if(panel.querySelector('.server-answer'))return;
  handled=question;const request=++sequence;controller?.abort();const active=new AbortController();controller=active;
  const expiry=setTimeout(()=>active.abort(),16000);
  try{
   const response=await fetch(root.dataset.api.replace(/\/$/,'')+'/answer',{
    method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({question,lang}),
    credentials:'omit',cache:'no-store',signal:active.signal
   });
   if(!response.ok)throw Error('answer unavailable');
   const result=await response.json();
   if(request!==sequence||input.value.trim().slice(0,300)!==question||!panel.isConnected)return;
   if(typeof result.answer!=='string'||!result.answer.trim())return;
   if(!['curated-answer-pack','server-ai'].includes(result.mode))return;
   if(result.mode==='server-ai'&&result.generative_available!==true)return;
   if(panel.querySelector('.server-answer'))return;
   const section=document.createElement('section');section.dataset.v6Answer='';section.className='panel server-answer';section.setAttribute('aria-live','polite');
   const heading=document.createElement('h2');heading.textContent=labels[result.mode==='server-ai'?1:0];
   const text=document.createElement('p');text.textContent=result.answer;text.style.whiteSpace='pre-wrap';section.append(heading,text);
   const sources=Array.isArray(result.sources)?[...new Set(result.sources)].slice(0,7):[];
   const list=document.createElement('ul');
   for(const source of sources){try{const url=new URL(source);if(url.protocol!=='https:'||url.username||url.password)continue;const li=document.createElement('li'),a=document.createElement('a');a.href=url.href;a.textContent=url.hostname;a.rel='noopener noreferrer';li.append(a);list.append(li);}catch{}}
   if(list.children.length){const title=document.createElement('strong');title.textContent=labels[2];section.append(title,list);}
   panel.prepend(section);
  }catch{
   if(request!==sequence||!panel.isConnected)return;
   const status=document.createElement('p');status.dataset.v6Answer='';status.className='quiet';status.setAttribute('role','status');status.textContent=labels[3];panel.append(status);
  }finally{clearTimeout(expiry);}
 }
 input.addEventListener('input',()=>{clear();schedule();});
 document.getElementById('search-form')?.addEventListener('submit',()=>{clear();schedule();});
 new MutationObserver(schedule).observe(target,{childList:true,subtree:true});
 schedule();
})();
