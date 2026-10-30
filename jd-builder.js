tyGuard('recruiter');
const $=i=>document.getElementById(i),F=['title','company','industry','location','openings','reporting','expMin','expMax','mode','type','education','pay','joining','about','purpose','resp','must','good','beh','cond','benefits','growth'];
let cur=null;
const brief=()=>Object.fromEntries(F.map(k=>[k,$(k).value]));
function secs(){$('secs').innerHTML=JD_ORDER.map(k=>`<label class="jd-sec"><span class="sec-h">${k}${cur.ai&&k!=='Equal opportunity'&&k!=='How to apply'?` <button type="button" class="logout ai-imp" data-k="${k}">✦ Improve</button>`:''}</span><textarea data-k="${k}" rows="${Math.max(3,Math.min(10,(cur.sections[k]||'').split('\n').length+1))}">${(cur.sections[k]||'').replace(/&/g,'&amp;').replace(/</g,'&lt;')}</textarea></label>`).join('')}
function check(){document.querySelectorAll('#secs textarea').forEach(t=>cur.sections[t.dataset.k]=t.value);
 const m=jdMissing(brief()),l=jdLint(cur.sections);
 $('miss').innerHTML=m.length?`<div class="gap-box"><b>Ask the client (${m.length} items missing)</b>${m.join(' · ')}</div>`:'<div class="insight">✓ <div><b>Brief is complete</b></div></div>';
 $('lint').innerHTML=l.length?`<div class="gap-box" style="border-color:#E5584F;background:#FBE9E7;margin-top:10px"><b style="color:#C2413B">Wording check: remove discriminatory wording</b>${l.join(' · ')}</div>`:''}
function saveCur(status,what){document.querySelectorAll('#secs textarea').forEach(t=>cur.sections[t.dataset.k]=t.value);if(status)cur.status=status;cur.history.push({t:new Date().toLocaleString(),what});jdPut(cur)}
$('gen').onclick=()=>{const b=brief();if(!b.title.trim()||!b.company.trim()){alert('Enter at least the job title and client company.');return}
 const id=cur?cur.id:jdNewId();b.reqId=id.replace('JD','TYC-REQ');cur={id,title:b.title,company:b.company,version:cur?cur.version:1,status:'Draft',sections:jdBuild(b),history:cur?cur.history:[{t:new Date().toLocaleString(),what:'Draft generated'}],brief:b};
 $('draft').hidden=false;secs();check();$('draft').scrollIntoView({behavior:'smooth'})};
$('secs').addEventListener('input',check);
$('save').onclick=()=>{saveCur(null,'Draft saved');$('msg').textContent='Saved.';list()};
$('copy').onclick=()=>{check();navigator.clipboard?.writeText(jdText(cur));$('msg').textContent='JD text copied.'};
$('send').onclick=()=>{check();if(jdLint(cur.sections).length&&!confirm('Wording check found issues. Send anyway?'))return;
 if(cur.status==='Changes requested'||cur.status==='Approved'){cur.version++}
 saveCur('Sent to client',`Version ${cur.version} sent to client`);const link=location.href.replace('jd-builder.html','jd-approval.html')+'#'+cur.id;
 $('msg').innerHTML=`Sent. Client approval link: <a class="text-link" href="jd-approval.html#${cur.id}" target="_blank">${link.split('/').slice(-1)[0]}</a> <button class="logout" onclick="navigator.clipboard?.writeText('${link}')">Copy link</button>`;list()};
function list(){const L=jdAll().reverse();$('list').innerHTML=L.length?'<table><thead><tr><th>JD ID</th><th>Role</th><th>Client</th><th>Version</th><th>Status</th><th>Client response</th><th>Action</th></tr></thead><tbody>'+L.map(j=>{const c={Approved:'g','Sent to client':'a','Changes requested':'r',Draft:''}[j.status]||'';return `<tr><td>${j.id}</td><td>${j.title}</td><td>${j.company}</td><td>v${j.version}</td><td><span class="tag ${c}">${j.status}</span></td><td style="white-space:normal;max-width:260px">${j.client&&j.status!=='Sent to client'&&j.status!=='Draft'?`${j.client.name} (${j.client.desig}): ${j.client.comment||'Approved'}`:'-'}</td><td><button class="logout" onclick="openJD('${j.id}')">Open</button> <a class="logout" href="jd-approval.html#${j.id}" target="_blank">Client view</a></td></tr>`}).join('')+'</tbody></table>':'<p class="note">No JDs yet.</p>'}
window.openJD=id=>{cur=jdGet(id);F.forEach(k=>$(k).value=(cur.brief||{})[k]||$(k).value);$('draft').hidden=false;secs();check();$('draft').scrollIntoView({behavior:'smooth'})};
window.addEventListener('focus',list);list();

async function api(body){const r=await fetch((window.TY_API||'')+'/api/generate-jd',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)});if(!r.ok)throw new Error(r.status);return r.json()}
function startDraft(sections,ai){const b=brief();const id=cur?cur.id:jdNewId();b.reqId=id.replace('JD','TYC-REQ');cur={id,title:b.title,company:b.company,version:cur?cur.version:1,status:'Draft',sections,ai,history:cur?cur.history:[],brief:b};cur.history.push({t:new Date().toLocaleString(),what:ai?'AI draft generated':'Draft generated'});
 $('draft').hidden=false;$('aiBanner').innerHTML=ai?'<div class="insight">✦ <div><b>AI-drafted. A recruiter must review every line before sending.</b><small>Check facts against the client brief. Bracketed items still need client answers.</small></div></div>':'';secs();check();$('draft').scrollIntoView({behavior:'smooth'})}
$('genAI').onclick=async()=>{const b=brief();if(!b.title.trim()||!b.company.trim()){alert('Enter at least the job title and client company.');return}
 $('aiState').textContent='Drafting with AI…';$('genAI').disabled=true;
 try{b.reqId='';const d=await api({brief:b});const base=jdBuild(b);const s={};JD_ORDER.forEach(k=>s[k]=(d.sections&&d.sections[k])||base[k]);startDraft(s,true);$('aiState').textContent=''}
 catch(e){$('aiState').textContent='AI is not available here, so the template draft was used.';startDraft(jdBuild(b),false)}
 $('genAI').disabled=false};
$('secs').addEventListener('click',async e=>{const btn=e.target.closest('.ai-imp');if(!btn)return;const k=btn.dataset.k,ta=document.querySelector(`#secs textarea[data-k="${k}"]`);
 const ins=prompt('How should AI improve this section?','Make it clearer and more concise');if(!ins)return;btn.textContent='Working…';
 try{const d=await api({action:'improve',section:k,text:ta.value,instruction:ins,brief:{title:cur.title,company:cur.company}});ta.value=d.text;check()}catch(x){alert('AI is not available right now.')}btn.textContent='✦ Improve'});
