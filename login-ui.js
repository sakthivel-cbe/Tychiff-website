/* Login / sign-up page behaviour: pipeline chart, password toggle, forgot-password mock */
(function(){
const S=[['Sourced','#2FAE66',[18,22,16,28,24,12,8,26,30,20,32,28,14,10]],['Screened','#1B9AD6',[12,15,10,18,16,8,5,17,20,14,22,19,9,6]],['Interview','#6B52C9',[6,8,9,7,11,5,3,10,12,8,14,11,6,4]],['Offer','#F2A93B',[2,3,4,2,5,3,1,4,6,3,7,5,2,2]]];
const D=['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
function path(p){let d=`M${p[0][0]},${p[0][1]}`;for(let i=0;i<p.length-1;i++){const a=p[i-1]||p[i],b=p[i],c=p[i+1],e=p[i+2]||c;d+=` C${b[0]+(c[0]-a[0])/6},${b[1]+(c[1]-a[1])/6} ${c[0]-(e[0]-b[0])/6},${c[1]-(e[1]-b[1])/6} ${c[0]},${c[1]}`}return d}
const el=document.getElementById('pipeChart');if(el){const W=560,H=250,L=34,R=10,T=12,B=26,max=32,x=i=>L+i*(W-L-R)/13,y=v=>T+(H-T-B)*(1-v/max);let g='';
 [0,8,16,24,32].forEach(v=>g+=`<line x1="${L}" x2="${W-R}" y1="${y(v)}" y2="${y(v)}" stroke="#E3EAEC" stroke-dasharray="3 4"/><text x="${L-8}" y="${y(v)+4}" text-anchor="end">${v}</text>`);
 for(let i=0;i<14;i++)g+=`<text x="${x(i)}" y="${H-8}" text-anchor="middle">${D[i%7]}</text>`;
 let defs='',lines='';S.forEach((s,k)=>{const pts=s[2].map((v,i)=>[x(i),y(v)]),d=path(pts);defs+=`<linearGradient id="g${k}" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="${s[1]}" stop-opacity=".28"/><stop offset="1" stop-color="${s[1]}" stop-opacity="0"/></linearGradient>`;
  lines+=`<path d="${d} L${x(13)},${y(0)} L${x(0)},${y(0)}Z" fill="url(#g${k})" class="area"/><path d="${d}" fill="none" stroke="${s[1]}" stroke-width="2.2" stroke-linecap="round" pathLength="1" class="ln"/>`});
 el.innerHTML=`<svg viewBox="0 0 ${W} ${H}" role="img" aria-label="Sample pipeline chart"><defs>${defs}</defs>${g}${lines}</svg><div class="lg">${S.map(s=>`<span><i style="background:${s[1]}"></i>${s[0]}</span>`).join('')}</div><small>SAMPLE PIPELINE · LAST 14 DAYS</small>`}
document.querySelectorAll('.pw-eye').forEach(b=>b.addEventListener('click',()=>{const i=b.parentElement.querySelector('input');const s=i.type==='password';i.type=s?'text':'password';b.textContent=s?'Hide':'Show'}));
const fp=document.getElementById('fp'),fb=document.getElementById('fpBox');
if(fp)fp.addEventListener('click',e=>{e.preventDefault();fb.hidden=!fb.hidden});
const fs=document.getElementById('fpSend');if(fs)fs.addEventListener('click',()=>{const v=document.getElementById('fpEmail').value.trim();document.getElementById('fpMsg').textContent=v?'If an account exists for this email, reset instructions will be sent. (Demo only: no email is sent.)':'Enter your email address first.'});
})();
