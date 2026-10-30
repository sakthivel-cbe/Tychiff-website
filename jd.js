/* JD store (browser localStorage demo). Replace with your backend / database. */
const JD_KEY='ty_jds';
function jdAll(){try{return JSON.parse(localStorage.getItem(JD_KEY))||[]}catch(e){return[]}}
function jdSave(l){try{localStorage.setItem(JD_KEY,JSON.stringify(l))}catch(e){}}
function jdGet(id){return jdAll().find(j=>j.id===id)}
function jdPut(j){const l=jdAll();const i=l.findIndex(x=>x.id===j.id);i>=0?l[i]=j:l.push(j);jdSave(l)}
function jdNewId(){return 'JD-'+Math.floor(1000+Math.random()*9000)}
const JD_ORDER=['Job snapshot','About the company','Role purpose','Key responsibilities','Must-have requirements','Good-to-have','Behavioural competencies','Work conditions','Compensation and benefits','Hiring process','Growth path','Equal opportunity','How to apply'];
const JD_BAD=/\b(male|female|men only|women only|unmarried|married|age limit|below \d+ years|above \d+ years|\d+\s*(?:-|to)\s*\d+\s*years old|young|good looking)\b/i;
function jdBuild(b){const v=(x,p)=>(x&&x.trim())?x.trim():'['+p+']';const lines=x=>(x||'').split('\n').map(s=>s.trim()).filter(Boolean);const bl=(x,p)=>lines(x).length?lines(x).map(s=>'• '+s.replace(/^[•\-\*]\s*/,'')).join('\n'):'• ['+p+']';
 const exp=(b.expMin||b.expMax)?`${b.expMin||0} to ${b.expMax||'+'} years`:'[Experience range to confirm]';
 return {
 'Job snapshot':`Job title: ${v(b.title,'Title')}\nCompany: ${v(b.company,'Client company')}\nRequirement ID: ${b.reqId||'[To be generated]'}\nDepartment / industry: ${v(b.industry,'Industry')}\nLocation: ${v(b.location,'Location')}\nWork mode: ${b.mode}\nEmployment type: ${b.type}\nOpenings: ${v(b.openings,'Number of openings')}\nReporting to: ${v(b.reporting,'Reporting line')}\nExperience: ${exp}\nEducation: ${v(b.education,'Education')}\nPay: ${v(b.pay,'Pay band, state monthly or annual, fixed or CTC')}\nJoining: ${v(b.joining,'Notice period preference')}`,
 'About the company':v(b.about,'Two or three lines about the client company, to be confirmed by the client'),
 'Role purpose':b.purpose&&b.purpose.trim()?b.purpose.trim():`As a ${v(b.title,'Title')} at ${v(b.company,'Client company')}, you will [describe why the role exists and what success looks like, maximum 60 words].`,
 'Key responsibilities':bl(b.resp,'Add six to ten responsibilities, each starting with an action verb'),
 'Must-have requirements':bl(b.must,'Add five to eight must-have skills, experience and tools'),
 'Good-to-have':bl(b.good,'Add skills that add value but are not mandatory'),
 'Behavioural competencies':bl(b.beh,'Add three to five traits tied to the job'),
 'Work conditions':bl(b.cond,'Travel, shifts, own vehicle or laptop, working days'),
 'Compensation and benefits':'Pay: '+v(b.pay,'Pay band')+'\n'+bl(b.benefits,'Incentives, allowances, insurance and other benefits'),
 'Hiring process':'1. Application screening by Tychiff Solutions\n2. Telephone discussion on interest, availability and expectations\n3. Interview with the client [number of rounds to confirm]\n4. Offer and joining confirmation',
 'Growth path':v(b.growth,'Next role or skills the candidate will build'),
 'Equal opportunity':'We welcome applications from all qualified candidates. Selection is based on skills, experience and fit for the role.',
 'How to apply':'Share your resume with Tychiff Solutions at clients@tychiff.com or call +91 80958 58644.'}}
function jdMissing(b){const q=[['title','Job title'],['company','Client company'],['location','Location'],['openings','Number of openings'],['expMin','Experience range'],['education','Education'],['pay','Pay band (monthly or annual, fixed or CTC)'],['resp','Key responsibilities'],['must','Must-have skills'],['reporting','Reporting line'],['joining','Notice period preference'],['cond','Travel and work conditions']];return q.filter(([k])=>!(b[k]&&String(b[k]).trim())).map(x=>x[1])}
function jdLint(sections){const out=[];for(const [k,t] of Object.entries(sections)){const m=t.match(JD_BAD);if(m)out.push(`"${m[0]}" in ${k}`)}return out}
function jdText(j){return JD_ORDER.filter(k=>j.sections[k]).map(k=>k.toUpperCase()+'\n'+j.sections[k]).join('\n\n')}
