/* TYCHIFF – demo authentication (front-end only, data kept in this browser's localStorage).
   Replace with your real backend (hashed passwords, JWT / SSO, email verification) before going live. */
const TY_SEED={
 candidate:{email:'candidate@tychiff.com',pass:'Candidate@123',name:'Arun Kumar'},
 recruiter:{email:'recruiter@tychiff.com',pass:'Recruiter@123',name:'Priya Nair'},
 admin:{email:'admin@tychiff.com',pass:'Admin@123',name:'System Admin'},
 management:{email:'management@tychiff.com',pass:'Management@123',name:'Sakthivel D.'}};
const TY_HOME={candidate:'portal-candidate.html',recruiter:'portal-recruiter.html',admin:'portal-admin.html',management:'portal-management.html'};
const TY_ADMIN_INVITE='TYCHIFF-ADMIN-2026';
function tyUsers(){try{return JSON.parse(localStorage.getItem('ty_users'))||[]}catch(e){return[]}}
function tySave(u){try{localStorage.setItem('ty_users',JSON.stringify(u))}catch(e){}}
function tyAll(){return Object.entries(TY_SEED).map(([r,u])=>({...u,role:r,status:'active',seed:true})).concat(tyUsers())}
function tyFind(role,email){email=email.trim().toLowerCase();return tyAll().find(u=>u.role===role&&u.email===email)}
function tySignup(role,d){
 const email=d.email.trim().toLowerCase();
 if(tyAll().some(u=>u.email===email))return {ok:false,msg:'An account with this email already exists.'};
 if(role==='admin'&&d.invite!==TY_ADMIN_INVITE)return {ok:false,msg:'Invalid admin invite code.'};
 const status=role==='candidate'||role==='admin'?'active':'pending';
 const list=tyUsers();list.push({role,email,pass:d.pass,name:d.name.trim(),phone:d.phone,extra:d.extra||'',status,created:new Date().toISOString().slice(0,10)});tySave(list);
 return {ok:true,status};}
function tyLogin(role,email,pass){window.tyErr='';const u=tyFind(role,email);
 if(!u||u.pass!==pass){window.tyErr='Invalid email or password for the '+role+' portal.';return false}
 if(u.status==='pending'){window.tyErr='Your account is awaiting admin approval.';return false}
 if(u.status==='rejected'){window.tyErr='Your access request was declined. Contact your administrator.';return false}
 sessionStorage.setItem('ty_session',JSON.stringify({role,name:u.name,email:u.email}));location.href=TY_HOME[role];return true}
function tyGuard(role){let s=null;try{s=JSON.parse(sessionStorage.getItem('ty_session'))}catch(e){}
 if(!s||s.role!==role){location.replace('login-'+role+'.html');return null}
 document.querySelectorAll('[data-user]').forEach(e=>e.textContent=s.name);return s}
function tyLogout(){let r='candidate';try{r=JSON.parse(sessionStorage.getItem('ty_session')).role||r}catch(e){}sessionStorage.removeItem('ty_session');location.href='login-'+r+'.html'}
function tySetStatus(email,status){const l=tyUsers();const u=l.find(x=>x.email===email);if(u){u.status=status;tySave(l)}}
function tyStartSession(role,email){const u=tyFind(role,email);if(!u)return 'No such account.';if(u.status!=='active')return u.status==='pending'?'Your account is awaiting admin approval.':'Your access was declined.';
 sessionStorage.setItem('ty_session',JSON.stringify({role,name:u.name,email:u.email}));location.href=TY_HOME[role];return ''}
