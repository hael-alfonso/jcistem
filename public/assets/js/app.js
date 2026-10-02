(function(){
  const D=JCI_DATA, UI=JCI_UI, P=JCI_PAGES;
  const menu=[
    {group:'Overview',items:[['dashboard','Dashboard','dashboard']]},
    {group:'Projects',items:[
      ['projects/all','Projects','briefcase'],['projects/create','Create Project','filePlus'],['projects/pending','Project Review','clipboardCheck'],
      ['tasks','Tasks & Milestones','checklist'],['loi','Letters of Intent','fileSignature'],['calendar','Calendar','calendar']
    ]},
    {group:'Reports & Finance',items:[['reports','Project Reports','report'],['finance','Financial Monitoring','chart']]},
    {group:'Organization',items:[['members','Members & Accounts','users'],['members/registration','Member Registration','userPlus'],['dues','My Member Dues','receipt'],['notifications','Notifications','bell']]},
    {group:'Account',items:[['account','My Account','user'],['audit','Audit Log','shieldCheck']]}
  ];
  function route(){ return (location.hash.replace(/^#/,'')||'dashboard').replace(/\/+$/,''); }
  function activeRoute(r,path){
    if(r===path) return true;
    if(path==='projects/all' && (r.startsWith('projects/') || r.startsWith('project/'))) return true;
    if(path==='finance' && (r==='finance' || r.startsWith('finance/'))) return true;
    if(path==='members' && (r==='members' || r.startsWith('members/'))) return true;
    return false;
  }
  function layout(content){
    const unread=D.notifications.filter(n=>!n.read).length;
    document.getElementById('app').innerHTML=`<div class="shell"><aside class="sidebar" id="sidebar"><div class="brand"><div class="brand-mark"><img src="assets/images/brand/favicon.png" alt="" class="system-logo"></div><div><strong>JCISTEM</strong><span>Project Management System</span></div></div><div class="workspace"><span>Signed in as</span><strong>${D.config.currentUser}</strong><small>Admin • Monitoring & Workflow</small></div><nav class="sidebar-nav" aria-label="Admin navigation">${menu.map(g=>`<div class="nav-group"><span class="nav-label">${g.group}</span>${g.items.map(([path,label,icon])=>`<a href="#${path}" class="nav-link ${activeRoute(route(),path)?'active':''}" aria-current="${activeRoute(route(),path)?'page':'false'}"><span class="nav-icon">${UI.icon(icon)}</span><span>${label}</span>${path==='notifications'&&unread?`<b class="nav-count">${unread}</b>`:''}</a>`).join('')}</div>`).join('')}</nav><div class="sidebar-foot"><a href="#account" class="account-chip">${UI.avatar(D.config.currentUser,'sm')}<span><strong>${D.config.currentUser}</strong><small>Admin</small></span><em>${UI.icon('chevron')}</em></a><div class="brand-footer">JCISTEM • Made by Pro WebDev</div></div></aside><div class="sidebar-overlay" id="sidebarOverlay"></div><main class="main"><header class="topbar"><div class="top-left"><button class="mobile-toggle" id="mobileToggle" aria-label="Open navigation" title="Open navigation">${UI.icon('menu')}</button><div class="top-title"><span class="top-eyebrow">JCISTEM • ADMIN</span><h2>${titleFor(route())}</h2><small class="top-context">Projects • Monitoring • Reports</small></div></div><div class="top-actions"><a class="icon-btn" href="#notifications" title="Notifications">${UI.icon('bell')}<b class="notification-badge">${unread}</b></a><a class="profile-pill" href="#account">${UI.avatar(D.config.currentUser,'sm')}<span>Admin</span>${UI.icon('chevron')}</a></div></header><section class="content" id="content">${content}</section><footer class="footer"><span><strong>JCISTEM</strong> • Online Project Management System</span><span>Admin Workspace • Made by Pro WebDev</span></footer></main></div>`;
    bindCommon();
  }
  function titleFor(r){
    const last=r.split('/');
    if(r==='dashboard') return 'Dashboard';
    if(r.startsWith('project/')) return 'Project Details';
    if(r.startsWith('projects/')) return ({all:'Projects',create:'Create Project',my:'My Projects',pending:'Project Review',approved:'Approved Projects',ongoing:'Ongoing Projects',completed:'Completed Projects',archived:'Archived Projects'}[last[1]]||'Projects');
    if(r.startsWith('finance/')) return ({budget:'Budget Allocation',utilization:'Fund Utilization',expenses:'Expense Monitoring',reports:'Financial Reports'}[last[1]]||'Financial Monitoring');
    return ({tasks:'Tasks & Milestones',loi:'Letters of Intent',calendar:'Calendar',reports:'Project Reports',members:'Members & Accounts','members/registration':'Member Registration',dues:'My Member Dues',notifications:'Notifications',account:'My Account',audit:'Audit Log'}[r]||'Admin System');
  }
  function bindCommon(){
    const t=document.getElementById('mobileToggle'), s=document.getElementById('sidebar'), overlay=document.getElementById('sidebarOverlay');
    const setMenu=(open)=>{if(!s)return;s.classList.toggle('open',open);overlay?.classList.toggle('show',open);document.body.classList.toggle('menu-open',open);t?.setAttribute('aria-expanded',String(open));};
    if(t)t.onclick=()=>setMenu(!s.classList.contains('open'));
    overlay?.addEventListener('click',()=>setMenu(false));
    document.querySelectorAll('.nav-link,.account-chip,.profile-pill').forEach(a=>a.addEventListener('click',()=>setMenu(false)));
    const activeNav=document.querySelector('.sidebar-nav .nav-link.active');
    if(activeNav) requestAnimationFrame(()=>activeNav.scrollIntoView({block:'nearest',inline:'nearest'}));
    document.addEventListener('keydown',e=>{if(e.key==='Escape')setMenu(false)});
    const search=document.getElementById('projectSearch'); if(search) search.addEventListener('input',()=>{const q=search.value.toLowerCase();document.querySelectorAll('.project-card').forEach(c=>c.style.display=c.innerText.toLowerCase().includes(q)?'':'none');});
    document.querySelectorAll('[data-tab]').forEach(btn=>btn.addEventListener('click',()=>loadProjectTab(btn.dataset.tab)));
    document.querySelectorAll('[data-task]').forEach(b=>b.onclick=()=>{const x=D.tasks.find(t=>t.id==b.dataset.task); UI.modal({title:x.title,body:`<div class="detail-grid"><div><span>Project</span><strong>${D.helpers.project(x.project)?.title}</strong></div><div><span>Assigned user</span><strong>${x.assignee}</strong></div><div><span>Deadline</span><strong>${D.helpers.date(x.deadline)}</strong></div><div><span>Status</span><strong>${x.status}</strong></div><div><span>Milestone</span><strong>${x.milestone}</strong></div><div><span>Notes</span><strong>${x.notes}</strong></div></div>`,footer:'<button class="btn btn-secondary" data-close>Close</button>'});});
    document.querySelectorAll('[data-loi]').forEach(b=>b.onclick=()=>{const x=D.lois.find(l=>l.id==b.dataset.loi),p=D.helpers.project(x.project);UI.modal({title:x.ref,body:`<div class="detail-grid"><div><span>Project reference</span><strong>PRJ-${String(x.project).padStart(4,'0')}</strong></div><div><span>Project title</span><strong>${p?.title}</strong></div><div><span>Project description</span><strong>${p?.needs}</strong></div><div><span>Recipient / partner</span><strong>${x.partner}</strong></div><div><span>Subject / purpose</span><strong>${x.purpose}</strong></div><div><span>Version</span><strong>${x.version}</strong></div></div>`,footer:'<button class="btn btn-secondary" data-close>Close</button>',size:'lg'});});
    document.querySelectorAll('[data-report]').forEach(b=>b.onclick=()=>{const x=D.reports.find(r=>r.id==b.dataset.report);UI.modal({title:x.title,body:`<div class="report-preview"><div class="report-meta"><span>${x.type}</span><span>${x.submitted}</span></div><h4>${x.title}</h4><p>Demo report preview for Admin viewing.</p><div class="report-cover">${UI.icon('report')}<strong>${x.pages} pages</strong></div></div>`,footer:`<button class="btn btn-secondary" data-close>Close</button><button class="btn btn-primary">${UI.icon('download')}Download</button>`});});
    const add=document.getElementById('addEvent'); if(add) add.onclick=()=>{const m=UI.modal({title:'Add Calendar Activity',body:`<form id="eventForm" class="form-grid"><div>${UI.field('Activity title',UI.input('title','','Activity title'))}</div><div>${UI.field('Activity type',UI.select('type',['Project','Meeting','Review']))}</div><div>${UI.field('Date',UI.input('date','','YYYY-MM-DD','date'))}</div><div>${UI.field('Time',UI.input('time','','HH:MM','time'))}</div><div class="full">${UI.field('Notes',UI.textarea('notes','','Schedule notes'))}</div></form>`,footer:`<button class="btn btn-secondary" data-close>Cancel</button><button class="btn btn-primary" id="saveEvent">${UI.icon('check')}Save activity</button>`});m.querySelector('#saveEvent').onclick=()=>{UI.toast('Calendar activity saved to the local demo session.','success');m.remove();};};
    const save=document.getElementById('saveMember'); if(save) save.onclick=()=>{const f=document.querySelector('form'); const name=f?.querySelector('[name=fullName]')?.value.trim(); if(!name){UI.toast('Enter the member full name before saving.','info');return;} UI.toast(`Registration saved for ${name}.`,'success');};
    const createAccount=document.getElementById('createAccount'); if(createAccount) createAccount.onclick=()=>{const f=document.querySelector('form'); const name=f?.querySelector('[name=fullName]')?.value.trim(); if(!name){UI.toast('Enter the member full name before preparing the account.','info');return;} UI.toast(`Account setup prepared for ${name}.`,'success');};
    const mark=document.getElementById('markAllRead'); if(mark) mark.onclick=()=>{D.notifications.forEach(n=>n.read=true);render();UI.toast('All notifications marked as read.','success');};
    document.querySelectorAll('[data-member]').forEach(b=>b.onclick=()=>{const x=D.users.find(u=>u.id==b.dataset.member);UI.modal({title:x.name,body:`<div class="detail-grid"><div><span>Member No.</span><strong>${x.memberNo}</strong></div><div><span>Email</span><strong>${x.email}</strong></div><div><span>Role</span><strong>${x.role}</strong></div><div><span>Status</span><strong>${x.status}</strong></div><div><span>Joined</span><strong>${D.helpers.date(x.joined)}</strong></div></div>`,footer:'<button class="btn btn-secondary" data-close>Close</button>'});});
  }
  function loadProjectTab(tab){
    const id=route().split('/')[1], p=D.helpers.project(id), el=document.getElementById('projectTabContent'); if(!el||!p)return;
    const tasks=D.helpers.projectTasks(id), docs=D.helpers.projectDocs(id), lois=D.helpers.projectLois(id), reports=D.reports.filter(r=>String(r.project)===String(id));
    document.querySelectorAll('[data-tab]').forEach(b=>b.classList.toggle('active',b.dataset.tab===tab));
    if(tab==='tasks') el.innerHTML=JCI_UI.card('Tasks & milestones','Project-scoped task monitoring',JCI_UI.table(['Task','Assignee','Deadline','Priority','Status'],tasks.map(t=>`<tr><td>${t.title}</td><td>${t.assignee}</td><td>${D.helpers.date(t.deadline)}</td><td>${UI.badge(t.priority)}</td><td>${UI.badge(t.status)}</td></tr>`)));
    else if(tab==='finance') el.innerHTML=JCI_UI.card('Budget & Funds','Project-level monitoring view',`<div class="stats-grid"><div>${UI.stat('Target / proposed',UI.money(p.targetBudget),'Source project proposal','wallet')}</div><div>${UI.stat('Approved allocation',UI.money(p.approvedBudget),'Approved budget','wallet')}</div><div>${UI.stat('Actual expenses',UI.money(p.usedFunds),'Validated records','chart')}</div><div>${UI.stat('Remaining',UI.money(Math.max(0,p.approvedBudget-p.usedFunds)),'Approved allocation minus expenses','wallet')}</div></div>`);
    else if(tab==='loi') el.innerHTML=JCI_UI.card('Linked LOIs','Controlled project-to-LOI linkage',JCI_UI.table(['Reference','Partner','Subject','Version','Status'],lois.map(l=>`<tr><td>${l.ref}</td><td>${l.partner}</td><td>${l.subject}</td><td>v${l.version}</td><td>${UI.badge(l.status)}</td></tr>`)));
    else if(tab==='docs') el.innerHTML=JCI_UI.card('Project documents','Completion evidence and source documents',JCI_UI.table(['Document','Type','Version','Updated'],docs.map(d=>`<tr><td>${d.name}</td><td>${d.type}</td><td>${d.version}</td><td>${d.updated}</td></tr>`)));
    else if(tab==='reports') el.innerHTML=JCI_UI.card('Project reports','Submitted and available reports',JCI_UI.table(['Report','Type','Submitted by','Status'],reports.map(r=>`<tr><td>${r.title}</td><td>${r.type}</td><td>${r.submittedBy}</td><td>${UI.badge(r.status)}</td></tr>`)));
    else el.innerHTML=`<div class="grid-2"><div>${UI.card('Project summary','Controlled project-linked data',`<div class="detail-grid"><div><span>Objectives</span><strong>${p.objectives}</strong></div><div><span>Beneficiaries</span><strong>${p.beneficiaries}</strong></div><div><span>Expected outputs</span><strong>${p.outputs}</strong></div><div><span>Expected outcomes</span><strong>${p.outcomes}</strong></div></div>`)}</div><div>${UI.card('Project timeline','Status and milestones',`<div class="timeline"><div><b>Proposal and approval</b><span>Workflow record retained with review history.</span></div><div><b>Implementation</b><span>${p.status} • ${p.progress}% progress.</span></div><div><b>Target / completion</b><span>${D.helpers.date(p.date)} • ${p.venue}</span></div></div>`)}</div></div>`;
  }
  function render(){
    const r=route(); let c='';
    if(r==='dashboard') c=P.dashboard();
    else if(r==='projects' || r.startsWith('projects/')) c=P.projects(r);
    else if(r.startsWith('project/')) c=P.project(r);
    else if(r==='tasks') c=P.tasks();
    else if(r==='loi') c=P.loi();
    else if(r==='calendar') c=P.calendar();
    else if(r==='reports') c=P.reports();
    else if(r==='finance' || r.startsWith('finance/')) c=P.finance(r);
    else if(r==='members' || r.startsWith('members/')) c=P.members(r);
    else if(r==='dues') c=P.dues();
    else if(r==='notifications') c=P.notifications();
    else if(r==='account') c=P.account();
    else if(r==='audit') c=P.audit();
    else c=P.notFound();
    layout(c);
  }
  window.addEventListener('hashchange',render); render();
})();
