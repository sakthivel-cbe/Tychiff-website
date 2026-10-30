/* Biometric (passkey) and security-key / smart-card sign-in using the browser's WebAuthn API.
   DEMO LEVEL: credentials are matched in this browser. A real deployment must verify the signed
   challenge on a server (see README-SSO.md). Works only on https or localhost, not file:// */
(function(){
const enc=new TextEncoder(),K='ty_creds';
const b64=b=>btoa(String.fromCharCode(...new Uint8Array(b))).replace(/\+/g,'-').replace(/\//g,'_').replace(/=+$/,'');
const unb=s=>Uint8Array.from(atob(s.replace(/-/g,'+').replace(/_/g,'/')),c=>c.charCodeAt(0));
const creds=()=>{try{return JSON.parse(localStorage.getItem(K))||[]}catch(e){return[]}};
const save=l=>{try{localStorage.setItem(K,JSON.stringify(l))}catch(e){}};
function ready(){if(location.protocol==='file:')return 'Biometric and card sign-in need a secure web address (https). They do not work when the file is opened directly from your computer.';
 if(!window.isSecureContext||!window.PublicKeyCredential)return 'This browser or address does not support biometric or security-key sign-in.';return ''}
const opts=k=>k==='biometric'?{authenticatorAttachment:'platform',userVerification:'required',residentKey:'preferred'}:{authenticatorAttachment:'cross-platform',userVerification:'preferred'};
window.tyRegisterCred=async function(kind,s){const m=ready();if(m)throw new Error(m);
 const c=await navigator.credentials.create({publicKey:{challenge:crypto.getRandomValues(new Uint8Array(32)),rp:{name:'Tychiff Solutions',id:location.hostname},user:{id:enc.encode(s.email),name:s.email,displayName:s.name},pubKeyCredParams:[{type:'public-key',alg:-7},{type:'public-key',alg:-257}],authenticatorSelection:opts(kind),timeout:60000,attestation:'none'}});
 const l=creds();l.push({id:b64(c.rawId),email:s.email,role:s.role,kind,label:kind==='biometric'?'This device (fingerprint / face / PIN)':'SSO card (smart card or security key)',created:new Date().toLocaleDateString()});save(l)};
window.tyCredList=s=>creds().filter(c=>c.email===s.email&&c.role===s.role);
window.tyRemoveCred=id=>save(creds().filter(c=>c.id!==id));
async function login(role,kind,email){const m=ready();if(m)return m;
 const list=creds().filter(c=>c.role===role&&c.kind===kind&&(!email||c.email===email.trim().toLowerCase()));
 if(!list.length)return kind==='biometric'?'No biometric is registered for this portal on this browser. Sign in with your password, then add it under Sign-in methods.':'No SSO card is registered for this portal. Sign in with your password, then add your card under Sign-in methods.';
 try{const a=await navigator.credentials.get({publicKey:{challenge:crypto.getRandomValues(new Uint8Array(32)),rpId:location.hostname,allowCredentials:list.map(c=>({type:'public-key',id:unb(c.id)})),userVerification:kind==='biometric'?'required':'preferred',timeout:60000}});
  const hit=list.find(c=>c.id===b64(a.rawId));return hit?tyStartSession(role,hit.email):'This credential is not registered here.'}
 catch(e){return e.name==='NotAllowedError'?'Sign-in was cancelled or timed out.':'Could not complete sign-in: '+e.message}}
document.addEventListener('DOMContentLoaded',()=>{const q=i=>document.getElementById(i),msg=q('altMsg');if(!msg)return;const role=(location.pathname.match(/login-(\w+)\.html/)||[])[1];
 const say=t=>{msg.textContent=t};
 q('bBio')?.addEventListener('click',async()=>{say('');say(await login(role,'biometric',q('e').value))});
 q('bCard')?.addEventListener('click',async()=>{say('');const c=window.TY_SSO||{};if(c.enabled&&c.url){location.href=c.url+(c.url.includes('?')?'&':'?')+'portal='+role;return}say(await login(role,'card',q('e').value))});
});

/* Security panel inside portals */
document.addEventListener('DOMContentLoaded',()=>{const main=document.querySelector('.app-main');if(!main||!document.querySelector('[data-user]'))return;let s;try{s=JSON.parse(sessionStorage.getItem('ty_session'))}catch(e){}if(!s)return;
 const p=document.createElement('section');p.className='panel';p.id='security';main.insertBefore(p,main.querySelector(':scope > p.note:last-of-type')||null);
 const draw=(m)=>{const l=tyCredList(s);p.innerHTML=`<h3>Sign-in methods</h3><p class="note" style="margin:-6px 0 14px">Add a faster, safer way to sign in to your ${s.role} account on this browser.</p>`+
 (l.length?'<table><thead><tr><th>Method</th><th>Added</th><th></th></tr></thead><tbody>'+l.map(c=>`<tr><td>${c.kind==='biometric'?'Biometric':'SSO card'} · ${c.label}</td><td>${c.created}</td><td><button class="logout" data-rm="${c.id}">Remove</button></td></tr>`).join('')+'</tbody></table>':'<p class="note">No biometric or card added yet.</p>')+
 `<div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:14px"><button class="btn btn-primary" data-add="biometric">Add biometric (fingerprint / face)</button><button class="btn nav-login" data-add="card">Add SSO card</button></div><p class="${m&&m.err?'err':'ok'}" style="margin-top:10px">${m?m.t:''}</p>`};
 draw();p.addEventListener('click',async e=>{const a=e.target.closest('[data-add]'),r=e.target.closest('[data-rm]');
  if(r){tyRemoveCred(r.dataset.rm);draw({t:'Removed.'})}
  if(a){try{await tyRegisterCred(a.dataset.add,s);draw({t:'Added. You can now use it on the sign-in page.'})}catch(x){draw({err:1,t:x.name==='NotAllowedError'?'Cancelled or timed out.':x.message})}}})});
})();
