/* Sample open roles. In production these come from the Requirement master (only "Publish to website" roles). */
const TY_JOBS=[
{id:'J1',cat:'IT & Software',title:'Java Developer',company:'Product company',loc:'Chennai',exp:'3–5 years',type:'Full-time',pay:'₹8–12 LPA',posted:'4 days ago',desc:'Build and maintain Spring Boot microservices. Strong Java, REST APIs and SQL required.'},
{id:'J2',cat:'Accounts',title:'Senior Accountant',company:'Manufacturing company',loc:'Ambattur, Chennai',exp:'4+ years',type:'Full-time',pay:'₹5–7 LPA',posted:'6 days ago',desc:'Own books of accounts, GST and TDS filings, and month-end closing. Tally and Excel proficiency.'},
{id:'J3',cat:'Retail & Sales',title:'Field Sales Executive',company:'BFSI company',loc:'Chennai',exp:'0–2 years',type:'Full-time',pay:'₹2.5–3.5 LPA + incentives',posted:'7 days ago',desc:'Generate leads and onboard customers in assigned areas. Two-wheeler and local-language skills preferred.'},
{id:'J4',cat:'Banking & Finance',title:'HR Recruiter',company:'Financial services company',loc:'Guindy, Chennai',exp:'1–3 years',type:'Full-time',pay:'₹3–4.5 LPA',posted:'9 days ago',desc:'End-to-end recruitment for banking roles: sourcing, screening and interview coordination.'},
{id:'J5',cat:'Healthcare',title:'Staff Nurse',company:'Multi-speciality hospital',loc:'Chennai',exp:'1+ years',type:'Full-time, rotational shifts',pay:'As per experience',posted:'11 days ago',desc:'Ward and ICU nursing roles. Valid nursing registration required.'},
{id:'J6',cat:'Logistics',title:'Warehouse Supervisor',company:'Logistics company',loc:'Sriperumbudur',exp:'3–6 years',type:'Full-time',pay:'₹3.5–5 LPA',posted:'13 days ago',desc:'Supervise inbound, storage and dispatch teams. WMS and inventory control experience.'},
{id:'J7',cat:'IT & Software',title:'QA Automation Engineer',company:'Software services company',loc:'Chennai',exp:'2–4 years',type:'Full-time',pay:'₹6–9 LPA',posted:'14 days ago',desc:'Design automated test suites using Selenium and API testing tools. (Sample role)'},
{id:'J8',cat:'Freshers & Campus',title:'Graduate Trainee',company:'Manufacturing company',loc:'Chennai',exp:'Fresher',type:'Full-time',pay:'₹2.2–3 LPA',posted:'15 days ago',desc:'Structured 6-month training programme with placement. 2024–2026 graduates. (Sample role)'}];
const TY_WA='918095858644';
function tyPub(){try{return JSON.parse(localStorage.getItem('ty_pub'))||{}}catch(e){return{}}}
function tyJobs(){const o=tyPub();return TY_JOBS.filter(j=>o[j.id]!==false)}
function tyJobCard(j){const m=encodeURIComponent('Hi TYCHIFF, I would like to apply for '+j.title+' ('+j.id+').');
 return `<article class="job-card"><div class="job-top"><span class="job-cat">${j.cat}</span><small>Posted ${j.posted}</small></div><h3>${j.title}</h3><p class="job-co">${j.company} · ${j.loc}</p>
 <div class="job-tags"><span>${j.exp}</span><span>${j.type}</span><span>${j.pay}</span></div>
 <div class="job-more" hidden>${j.desc}</div>
 <div class="job-foot"><button class="linkbtn" onclick="const d=this.closest('.job-card').querySelector('.job-more');d.hidden=!d.hidden;this.textContent=d.hidden?'View details':'Hide details'">View details</button><span><a class="wa" href="https://wa.me/${TY_WA}?text=${m}" target="_blank" rel="noopener">Apply on WhatsApp</a> · <a class="wa alt" href="login-candidate.html">Sign in to apply</a></span></div></article>`}
