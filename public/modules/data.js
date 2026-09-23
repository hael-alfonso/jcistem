window.JCI_DATA = {
  config: {
    version: 'Master Rebuild v43 — JCI Light Theme + Analytics',
    org: 'JCI Carmona',
    location: 'Carmona City, Cavite, Philippines',
    currentUser: 'Mich Alfonso',
    role: 'Admin',
    areas: ['Individual Development','Community Action','International Cooperation','Business & Entrepreneurship'],
    statuses: ['Draft','Submitted / Pending Review','Admin Review','Budget Review','BOD Review','Returned / Revision Required','Rejected','Approved — Chair Pending','Project Setup','Ongoing','Completion Review','Completed','Archived'],
    taskStatuses: ['Pending','In Progress','Completed','Overdue'],
    dueStatuses: ['Paid','Partial','Unpaid','Overdue','Waived/Adjusted']
  },
  users: [
    {id:1,name:'Mich Alfonso',initials:'MA',email:'admin@jcicarmona.org',role:'Admin',status:'Active',joined:'2025-01-12',memberNo:'JCI-001'},
    {id:2,name:'Juan Dela Cruz',initials:'JD',email:'juan@jcicarmona.org',role:'BOD',status:'Active',joined:'2025-02-18',memberNo:'JCI-002'},
    {id:3,name:'Maria Santos',initials:'MS',email:'maria@jcicarmona.org',role:'BOD',status:'Active',joined:'2025-03-04',memberNo:'JCI-003'},
    {id:4,name:'Carlo Mendoza',initials:'CM',email:'carlo@jcicarmona.org',role:'Treasurer',status:'Active',joined:'2025-01-25',memberNo:'JCI-004'},
    {id:5,name:'Mark Reyes',initials:'MR',email:'mark@jcicarmona.org',role:'General Member',status:'Pending',joined:'2026-08-19',memberNo:'JCI-005'},
    {id:6,name:'Ana Cruz',initials:'AC',email:'ana@jcicarmona.org',role:'General Member',status:'Active',joined:'2025-06-15',memberNo:'JCI-006'}
  ],
  projects: [
    {id:1,title:'JCI Carmona Community Impact Project',area:'Community Action',chair:'Juan Dela Cruz',date:'2026-10-15',venue:'Carmona City, Cavite',status:'Ongoing',progress:72,targetBudget:50000,approvedBudget:50000,usedFunds:11800,taskCount:3,needs:'Community need identified through local consultation.',objectives:'Deliver a practical and sustainable response with community partners.',beneficiaries:'Selected community members and partner groups.',outputs:'Community activity delivery, volunteer participation, evidence package.',outcomes:'Documented community results and a sustainability plan.',partners:'Local community office and partner groups',participants:'JCI members and volunteers',risks:'Weather, logistics, partner availability',resources:'Venue, volunteer kits, transport, communications',documents:3,lois:1,reportStatus:'Not yet submitted',createdBy:'Juan Dela Cruz'},
    {id:2,title:'JCI Carmona Leadership Development Program',area:'Individual Development',chair:'Maria Santos',date:'2026-11-08',venue:'Carmona City, Cavite',status:'Approved — Chair Pending',progress:48,targetBudget:35000,approvedBudget:35000,usedFunds:3200,taskCount:1,needs:'Members and young leaders need practical leadership development.',objectives:'Develop communication, project management and leadership capabilities.',beneficiaries:'JCI members and invited young leaders.',outputs:'Learning sessions, attendance, participant action plans.',outcomes:'Participants identify actions they can apply after the program.',partners:'Local Schools Network',participants:'Members and invited youth leaders',risks:'Speaker availability, attendance',resources:'Training materials, venue, certificates',documents:2,lois:1,reportStatus:'Not yet submitted',createdBy:'Maria Santos'},
    {id:3,title:'JCI Carmona Business & Entrepreneurship Forum',area:'Business & Entrepreneurship',chair:'—',date:'2026-10-25',venue:'Carmona City, Cavite',status:'Pending Review',progress:15,targetBudget:22000,approvedBudget:0,usedFunds:0,taskCount:0,needs:'Young professionals and entrepreneurs need learning and networking opportunities.',objectives:'Create a practical learning and networking experience.',beneficiaries:'Members, young professionals and aspiring entrepreneurs.',outputs:'Forum sessions, networking activities, action notes.',outcomes:'Useful connections and next-step actions.',partners:'Prospective business community partners',participants:'Members and invited entrepreneurs',risks:'Partner confirmation and attendance',resources:'Venue, speaker support, materials',documents:4,lois:0,reportStatus:'Not yet submitted',createdBy:'Mark Reyes'},
    {id:4,title:'JCI Carmona International Cooperation Activity',area:'International Cooperation',chair:'Ana Cruz',date:'2026-12-05',venue:'JCI Carmona Chapter',status:'Completed',progress:100,targetBudget:28000,approvedBudget:28000,usedFunds:24100,taskCount:4,needs:'Members benefit from opportunities to connect and learn beyond the local chapter.',objectives:'Build meaningful collaboration and exchange with JCI members or partners.',beneficiaries:'JCI Carmona members and participating partners.',outputs:'Exchange session, documentation and collaboration notes.',outcomes:'Shared learning and documented collaboration results.',partners:'JCI partner chapter',participants:'JCI members and partner delegates',risks:'Schedule coordination and travel changes',resources:'Program materials, venue, digital tools',documents:7,lois:1,reportStatus:'Submitted to Admin',createdBy:'Ana Cruz'}
  ],
  tasks:[
    {id:1,project:1,title:'Confirm community partner',assignee:'Juan Dela Cruz',deadline:'2026-10-02',priority:'High',status:'Completed',milestone:'Partnership',notes:'Coordinate confirmation letter.'},
    {id:2,project:1,title:'Prepare volunteer kits',assignee:'Ana Cruz',deadline:'2026-10-08',priority:'Medium',status:'In Progress',milestone:'Logistics',notes:'Prepare 60 kits.'},
    {id:3,project:1,title:'Coordinate venue logistics',assignee:'Mark Reyes',deadline:'2026-10-11',priority:'High',status:'Pending',milestone:'Logistics',notes:'Finalize tables, chairs and sound.'},
    {id:4,project:2,title:'Finalize speaker lineup',assignee:'Maria Santos',deadline:'2026-10-25',priority:'Medium',status:'In Progress',milestone:'Program',notes:'Confirm two youth speakers.'},
    {id:5,project:4,title:'Compile completion evidence',assignee:'Ana Cruz',deadline:'2026-09-28',priority:'Low',status:'Completed',milestone:'Completion',notes:'Collect photos and signed attendance.'}
  ],
  lois:[
    {id:1,ref:'LOI-2026-014',project:1,partner:'Carmona Community Office',recipient:'Community Partnerships Unit',subject:'Partnership and venue support',date:'2026-09-19',status:'Pending',version:'1.0',purpose:'Request partnership and venue support for community outreach.'},
    {id:2,ref:'LOI-2026-013',project:2,partner:'Local Schools Network',recipient:'Program Coordinators',subject:'Youth leadership participation',date:'2026-09-17',status:'Approved',version:'2.0',purpose:'Invite schools to participate in the youth leadership forum.'},
    {id:3,ref:'LOI-2026-009',project:4,partner:'JCI Partner Chapter',recipient:'Chapter President',subject:'International cooperation activity',date:'2026-08-28',status:'Approved',version:'1.2',purpose:'Coordinate exchange participation and shared activity schedule.'}
  ],
  expenses:[
    {id:1,project:1,date:'2026-09-12',category:'Supplies',description:'Volunteer materials',amount:6800,payee:'Community supplier',requester:'Juan Dela Cruz',ref:'EXP-1001',status:'Posted',reviewer:'Carlo Mendoza',receipt:true},
    {id:2,project:1,date:'2026-09-15',category:'Venue',description:'Venue reservation',amount:5000,payee:'Venue partner',requester:'Juan Dela Cruz',ref:'EXP-1002',status:'Posted',reviewer:'Carlo Mendoza',receipt:true},
    {id:3,project:2,date:'2026-09-16',category:'Materials',description:'Printing and certificates',amount:3200,payee:'Print provider',requester:'Maria Santos',ref:'EXP-1003',status:'Pending',reviewer:'—',receipt:true},
    {id:4,project:4,date:'2026-08-30',category:'Program',description:'Exchange activity materials',amount:24100,payee:'Program suppliers',requester:'Ana Cruz',ref:'EXP-0991',status:'Posted',reviewer:'Carlo Mendoza',receipt:true}
  ],
  allocations:[
    {id:1,project:1,category:'Program & Community Activities',allocated:30000,used:6800,notes:'Community outreach materials and program delivery.'},
    {id:2,project:1,category:'Logistics & Transport',allocated:12000,used:5000,notes:'Venue and logistics requirements.'},
    {id:3,project:1,category:'Contingency',allocated:8000,used:0,notes:'Reserved for approved contingencies.'},
    {id:4,project:2,category:'Program Expenses',allocated:22000,used:3200,notes:'Forum materials, certificates and program needs.'},
    {id:5,project:2,category:'Speakers & Venue',allocated:13000,used:0,notes:'Speaker support and venue requirements.'},
    {id:6,project:4,category:'International Program',allocated:28000,used:24100,notes:'Program and exchange activity expenses.'}
  ],
  ledger:[
    {id:1,date:'2026-09-12',ref:'TRX-1001',type:'Expense / Payment',description:'Volunteer materials',member:'',project:1,category:'Supplies',payer:'Community supplier',amount:6800,status:'Posted'},
    {id:2,date:'2026-09-15',ref:'TRX-1002',type:'Expense / Payment',description:'Venue reservation',member:'',project:1,category:'Venue',payer:'Venue partner',amount:5000,status:'Posted'},
    {id:3,date:'2026-09-03',ref:'DUES-0903',type:'Dues Collection',description:'September member dues',member:'Ana Cruz',project:'',category:'Member Dues',payer:'Ana Cruz',amount:500,status:'Posted'},
    {id:4,date:'2026-09-07',ref:'DUES-0907',type:'Dues Collection',description:'September member dues',member:'Juan Dela Cruz',project:'',category:'Member Dues',payer:'Juan Dela Cruz',amount:500,status:'Posted'},
    {id:5,date:'2026-08-30',ref:'TRX-0991',type:'Expense / Payment',description:'International cooperation activity materials',member:'',project:4,category:'Program',payer:'Program suppliers',amount:24100,status:'Posted'}
  ],
  dues:[
    {id:1,member:'Mich Alfonso',period:'September 2026',due:'2026-09-15',expected:500,paid:500,paymentDate:'2026-09-08',status:'Paid',ref:'DUES-0901'},
    {id:2,member:'Juan Dela Cruz',period:'September 2026',due:'2026-09-15',expected:500,paid:500,paymentDate:'2026-09-07',status:'Paid',ref:'DUES-0907'},
    {id:3,member:'Maria Santos',period:'September 2026',due:'2026-09-15',expected:500,paid:250,paymentDate:'2026-09-14',status:'Partial',ref:'DUES-0910'},
    {id:4,member:'Carlo Mendoza',period:'September 2026',due:'2026-09-15',expected:500,paid:0,paymentDate:'',status:'Overdue',ref:''},
    {id:5,member:'Mark Reyes',period:'September 2026',due:'2026-09-15',expected:500,paid:0,paymentDate:'',status:'Unpaid',ref:''},
    {id:6,member:'Ana Cruz',period:'September 2026',due:'2026-09-15',expected:500,paid:500,paymentDate:'2026-09-03',status:'Paid',ref:'DUES-0903'}
  ],
  reports:[
    {id:1,project:4,type:'Project Completion Report',title:'International Cooperation Activity — Completion Report',submittedBy:'Ana Cruz',submitted:'2026-09-10 15:40',status:'Submitted to Admin',pages:9},
    {id:2,project:4,type:'Overall Financial Report',title:'Overall Financial Report — August 2026',submittedBy:'Carlo Mendoza',submitted:'2026-09-05 17:20',status:'Submitted to Admin',pages:12},
    {id:3,project:1,type:'Project Progress Report',title:'Community Impact Project — September Progress',submittedBy:'Juan Dela Cruz',submitted:'2026-09-18 11:15',status:'Draft / Not Submitted',pages:5}
  ],
  documents:[
    {id:1,project:1,name:'Approved Project Proposal.pdf',type:'Project Proposal',version:'1.0',updated:'2026-09-06'},
    {id:2,project:1,name:'Partner Confirmation.pdf',type:'Supporting Document',version:'1.1',updated:'2026-09-19'},
    {id:3,project:1,name:'Venue Coordination.pdf',type:'Project Document',version:'1.0',updated:'2026-09-20'},
    {id:4,project:2,name:'Leadership Program Proposal.pdf',type:'Project Proposal',version:'1.0',updated:'2026-09-08'},
    {id:5,project:4,name:'Completion Evidence Pack.pdf',type:'Completion Evidence',version:'1.2',updated:'2026-09-11'}
  ],
  events:[
    {id:1,title:'Community Outreach Program',date:'2026-10-15',time:'08:00',type:'Project',project:1,notes:'Carmona City Hall'},
    {id:2,title:'BOD Project Review',date:'2026-10-05',time:'18:00',type:'Meeting',project:'',notes:'Chapter meeting room'},
    {id:3,title:'Youth Leadership Forum',date:'2026-11-08',time:'09:00',type:'Project',project:2,notes:'Carmona Sports Complex'},
    {id:4,title:'Completion Review — International Cooperation',date:'2026-09-29',time:'16:00',type:'Review',project:4,notes:'Admin review of completion records'}
  ],
  notifications:[
    {id:1,type:'Project',title:'Community Impact Project reached 72% progress.',time:'Today, 9:20 AM',read:false,route:'#project/1'},
    {id:2,type:'Approval',title:'Business & Entrepreneurship Forum is awaiting Admin review.',time:'Today, 8:40 AM',read:false,route:'#projects/pending'},
    {id:3,type:'Finance',title:'Youth Leadership Forum has a pending expense record to monitor.',time:'Yesterday, 3:10 PM',read:false,route:'#finance/expenses'},
    {id:4,type:'Report',title:'International Cooperation completion report was submitted to Admin.',time:'Sep 10, 2026',read:true,route:'#reports'},
    {id:5,type:'Member',title:'Mark Reyes submitted a member registration.',time:'Sep 19, 2026',read:true,route:'#members/registration'}
  ],
  audit:[
    {id:1,actor:'Juan Dela Cruz',action:'Submitted Project',object:'JCI Carmona Community Impact Project',project:1,time:'2026-09-18 11:15',remarks:'Submitted for Admin review.'},
    {id:2,actor:'Mich Alfonso',action:'Moved Review Stage',object:'JCI Carmona Community Impact Project',project:1,time:'2026-09-18 13:05',remarks:'Admin review started.'},
    {id:3,actor:'Carlo Mendoza',action:'Recorded Expense',object:'EXP-1002',project:1,time:'2026-09-15 17:30',remarks:'Receipt verified.'},
    {id:4,actor:'Ana Cruz',action:'Submitted Report',object:'Completion Report',project:4,time:'2026-09-10 15:40',remarks:'Sent to Admin.'}
  ]
};

window.JCI_DATA.helpers = {
  project(id){ return JCI_DATA.projects.find(p=>String(p.id)===String(id)); },
  member(name){ return JCI_DATA.users.find(u=>u.name===name); },
  projectExpenses(id){ return JCI_DATA.expenses.filter(e=>String(e.project)===String(id)); },
  projectTasks(id){ return JCI_DATA.tasks.filter(t=>String(t.project)===String(id)); },
  projectDocs(id){ return JCI_DATA.documents.filter(d=>String(d.project)===String(id)); },
  projectLois(id){ return JCI_DATA.lois.filter(l=>String(l.project)===String(id)); },
  currency(n){ return new Intl.NumberFormat('en-PH',{style:'currency',currency:'PHP',maximumFractionDigits:0}).format(Number(n||0)); },
  date(v){ if(!v) return '—'; const d=new Date(v); return isNaN(d)?v:d.toLocaleDateString('en-PH',{month:'short',day:'numeric',year:'numeric'}); },
  initials(name){ return (name||'—').split(/\s+/).map(x=>x[0]).slice(0,2).join('').toUpperCase(); },
  statusClass(s){
    s=String(s||'');
    if(/Completed|Approved|Paid|Posted|Active|Submitted to Admin/.test(s)) return 'positive';
    if(/Ongoing|In Progress|Pending|Partial|Review/.test(s)) return 'info';
    if(/Overdue|Rejected|Returned|Unpaid/.test(s)) return 'danger';
    if(/Archived|Draft|Waived/.test(s)) return 'muted';
    return 'neutral';
  }
};
