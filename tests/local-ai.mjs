import assert from 'node:assert/strict';import {localDraft} from '../wp-content/plugins/oberhub-core/assets/local-ai.mjs';
const record={title:{en:'Residence'},description_short:{en:'Ask the municipal office'},source_url:'https://www.oberriet.ch/'};
assert.equal((await localDraft([record],'en',{api:null})).mode,'deterministic');let created=false;
assert.equal((await localDraft([record],'en',{api:{availability:async()=> 'downloadable',create:async()=>{created=true;}}})).mode,'deterministic');assert.equal(created,false);
let destroyed=false;const out=await localDraft([record],'en',{api:{availability:async()=> 'available',create:async()=>({prompt:async()=> 'Draft',destroy:()=>destroyed=true})}});assert.equal(out.requires_review,true);assert.equal(out.answer,'Draft');assert.equal(destroyed,true);assert.deepEqual(out.sources,[record.source_url]);
assert.equal((await localDraft([record],'en',{api:{availability:async()=> {throw Error('unavailable')}}})).mode,'deterministic');console.log('PASS local AI unavailable/download/error fallback, opt-in draft provenance and session destruction');
