document.querySelector('.menu-toggle')?.addEventListener('click',()=>document.querySelector('.nav-links').classList.toggle('open'));
document.querySelectorAll('.nav-links a').forEach(a=>a.addEventListener('click',()=>document.querySelector('.nav-links').classList.remove('open')));
document.getElementById('requirementForm')?.addEventListener('submit',e=>{e.preventDefault();
 const id='TYC-REQ-'+Math.floor(1000+Math.random()*9000);
 document.getElementById('formMessage').textContent='Thank you. Requirement '+id+' is acknowledged. We will return a JD for your approval.';e.target.reset()});
