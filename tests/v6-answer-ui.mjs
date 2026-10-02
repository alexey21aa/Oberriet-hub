import fs from 'node:fs';
import assert from 'node:assert/strict';
import {chromium} from 'playwright';
import sparticuz from '@sparticuz/chromium';
const executablePath=process.env.OBERHUB_CHROMIUM_PATH||(fs.existsSync('/tmp/chromium')&&fs.statSync('/tmp/chromium').size>1000000?'/tmp/chromium':await sparticuz.executablePath());
const launch={executablePath,args:sparticuz.args.filter(x=>x!=='--single-process'),headless:true};const contexts=[];console.log('Launching browser');const shared=await chromium.launchPersistentContext('',launch);contexts.push(shared);const sharedPage=shared.pages()[0]||await shared.newPage();console.log('Browser ready');
const script=fs.readFileSync('wp-content/plugins/oberhub-v6/assets/server-answer.js','utf8');
const checks=[];const check=(name,passed)=>{checks.push({name,passed:!!passed});console.log((passed?'PASS ':'FAIL ')+name);};
const pack={mode:'curated-answer-pack',answer:'Проверенный ответ по источникам для детей.',sources:['https://provider.test/kids'],generative_available:false};
const payloads=[];
async function fixture(result,viewport={width:390,height:844},language='ru'){
 const page=sharedPage;await page.setViewportSize(viewport);await page.unrouteAll({behavior:'ignoreErrors'});const context={close:async()=>{}};
 await page.route('https://hub.test/**',async route=>{
  if(route.request().url().endsWith('/answer')){payloads.push(JSON.parse(route.request().postData()));if(typeof result==='function')return result(route);return route.fulfill({json:result});}
  return route.fulfill({contentType:'text/html; charset=utf-8',body:`<div id="oberhub-app" data-path="search" data-lang="${language}" data-api="https://hub.test/api"><form id="search-form"><input id="query" value="Спорт для детей"><button>Search</button></form><div id="search-results"><section class="answer-panel"><p>Existing verified options</p></section></div></div>`});
 });
 await page.goto('https://hub.test/search');await page.addScriptTag({content:script});return{context,page};
}
try{
 for(const width of [360,1920]){
  const {context,page}=await fixture(pack,{width,height:844});await page.locator('[data-v6-answer]').waitFor();
  check('Server answer visible at width '+width,(await page.locator('[data-v6-answer]').innerText()).includes(pack.answer));
  check('Curated answer is not labelled generated '+width,(await page.locator('[data-v6-answer] h2').innerText())==='Ответ по источникам');
  check('Source link displayed '+width,await page.locator('[data-v6-answer] a').getAttribute('href')==='https://provider.test/kids');await context.close();
 }
 check('Device-independent request',JSON.stringify(payloads[0])===JSON.stringify(payloads[1])&&Object.keys(payloads[0]).sort().join(',')==='lang,question');
 const unsafe={...pack,answer:'<img src=x onerror="window.BAD=true"> literal text',sources:['javascript:alert(1)','http://provider.test','https://user:secret@provider.test','https://provider.test/kids']};
 {const{context,page}=await fixture(unsafe);await page.locator('[data-v6-answer]').waitFor();check('Answer markup remains literal',(await page.locator('[data-v6-answer] p').innerText())===unsafe.answer&&await page.locator('[data-v6-answer] img').count()===0);check('Unsafe source URLs filtered',await page.locator('[data-v6-answer] a').count()===1);await context.close();}
 {const{context,page}=await fixture({...pack,mode:'server-ai',generative_available:true});await page.locator('[data-v6-answer]').waitFor();check('Generative mode labelled honestly',(await page.locator('[data-v6-answer] h2').innerText())==='Ответ ИИ по источникам');await context.close();}
 for(const result of [{mode:'evidence-pack',answer:null},{...pack,mode:'invented-mode'},{...pack,mode:'server-ai',generative_available:false}]){
  let responded;const done=new Promise(r=>responded=r);const{context,page}=await fixture(async route=>{await route.fulfill({json:result});responded();});await done;await page.waitForTimeout(50);check('No false answer for '+result.mode,await page.locator('[data-v6-answer]').count()===0&&(await page.locator('.answer-panel').innerText()).includes('Existing verified options'));await context.close();
 }
 {const{context,page}=await fixture(route=>route.fulfill({status:503,body:'Unavailable'}));await page.locator('[data-v6-answer]').waitFor();check('Failure preserves matching options',(await page.locator('.answer-panel').innerText()).includes('Existing verified options'));check('Failure is accessible',await page.locator('[data-v6-answer]').getAttribute('role')==='status');await context.close();}
 {let first;const started=new Promise(r=>first=r);const{context,page}=await fixture(async route=>{const q=JSON.parse(route.request().postData()).question;if(q==='Спорт для детей'){first();await new Promise(r=>setTimeout(r,1000));await route.fulfill({json:{...pack,answer:'OLD RESPONSE MUST NOT APPEAR'}}).catch(()=>{});}else await route.fulfill({json:{...pack,answer:'NEW CURRENT RESPONSE'}});});await started;await page.locator('#query').fill('Танцы для женщин');await page.getByText('NEW CURRENT RESPONSE',{exact:true}).waitFor();check('Outdated response discarded',!(await page.locator('.answer-panel').innerText()).includes('OLD RESPONSE'));await context.close();}
 console.log(JSON.stringify({checks:checks.length,failed:checks.filter(x=>!x.passed)}));fs.writeFileSync('tests/results/v6-answer-ui.json',JSON.stringify(checks,null,2)+'\n');assert(checks.every(x=>x.passed));
}catch(e){console.error(e);process.exitCode=1;}finally{await Promise.race([Promise.allSettled(contexts.map(c=>c.close())),new Promise(r=>setTimeout(r,2000))]);process.exit(process.exitCode||0);}
