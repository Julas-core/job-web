// Lightweight in-browser demo backend (localStorage)
(function(){
  const KEYS = { users:'jl_users', jobs:'jl_jobs', apps:'jl_apps', current:'jl_current_user' };

  const seedJobs = [
    {
      id:1,
      title:'Web Developer',
      company:'Allena',
      location:'Mekelle',
      salary:'15000/Month',
      type:'Full Time',
      posted:'2 months ago',
      category:'Engineering',
      description:'Own the frontend for Allena’s customer portal, delivering performant, responsive interfaces and collaborating closely with backend and design.',
      responsibilities:[
        'Build and ship modular UI components with modern JS.',
        'Integrate REST endpoints securely and handle edge cases.',
        'Continuously improve accessibility, performance, and DX.'
      ],
      requirements:[
        '2+ years building production web apps.',
        'Solid HTML, CSS, JavaScript fundamentals.',
        'Experience with accessibility and perf profiling.'
      ]
    },
    {
      id:2,
      title:'Digital Marketer',
      company:'Northern Star',
      location:'Dire Dawa',
      salary:'200/Hour',
      type:'Part Time',
      posted:'4 months ago',
      category:'Marketing',
      description:'Design and optimize multi-channel growth campaigns for Northern Star, with a focus on measurable funnel improvements.',
      responsibilities:[
        'Plan, launch, and iterate on paid and organic campaigns.',
        'Analyze channel performance and report actionable insights.',
        'Collaborate with content/design to align messaging and creatives.'
      ],
      requirements:[
        '1+ year in digital marketing or growth.',
        'Hands-on with analytics/attribution tools.',
        'Strong copy sense and audience targeting basics.'
      ]
    },
    {
      id:3,
      title:'Business Manager',
      company:'Pulte Homes',
      location:'Axsum',
      salary:'13000/Month',
      type:'Full Time',
      posted:'6 days ago',
      category:'Operations',
      description:'Lead operations at Pulte Homes, coordinating teams to hit quarterly targets and streamline processes.',
      responsibilities:[
        'Own quarterly goals and operational rhythms.',
        'Coordinate cross-functional stakeholders and timelines.',
        'Report KPIs and drive continuous process improvements.'
      ],
      requirements:[
        '3+ years in operations or business management.',
        'Proven stakeholder communication skills.',
        'Data-driven decision-making mindset.'
      ]
    }
  ];
  const seedUsers = [
    { id:'u-seeker', role:'Seeker', name:'Demo Seeker', email:'seeker@test.com', password:'StrongPass!23' },
    { id:'u-employer', role:'Employer', name:'Demo Employer', email:'employer@test.com', password:'StrongPass!23' }
  ];

  const load = (key, fallback) => { try { return JSON.parse(localStorage.getItem(key)) ?? fallback; } catch { return fallback; } };
  const save = (key, val) => localStorage.setItem(key, JSON.stringify(val));
  const ensureSeed = () => {
    if(!localStorage.getItem(KEYS.jobs)) save(KEYS.jobs, seedJobs);
    if(!localStorage.getItem(KEYS.users)) save(KEYS.users, seedUsers);
    if(!localStorage.getItem(KEYS.apps)) save(KEYS.apps, []);
  };

  const getCurrent = () => load(KEYS.current, null);
  const setCurrent = (user) => save(KEYS.current, user);
  const logout = () => localStorage.removeItem(KEYS.current);

  // Mobile navigation toggle for slide-in drawer
  const wireNav = () => {
    const navToggle = document.querySelector('.nav-toggle');
    if(!navToggle) return;
    const closeNav = () => {
      document.body.classList.remove('nav-open');
      navToggle.setAttribute('aria-expanded','false');
    };
    navToggle.addEventListener('click', ()=>{
      const open = document.body.classList.toggle('nav-open');
      navToggle.setAttribute('aria-expanded', String(open));
    });
    document.addEventListener('keydown', (e)=>{ if(e.key === 'Escape'){ closeNav(); } });
    document.querySelectorAll('.nav-links a, .auth-links a').forEach(link => link.addEventListener('click', closeNav));
  };

  // Simple inline validation for forms marked data-validate
  const wireValidation = () => {
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
      form.setAttribute('novalidate','true');
      const clearErrors = () => {
        form.querySelectorAll('.error-text').forEach(node => node.remove());
        form.querySelectorAll('[aria-invalid="true"]').forEach(f => f.removeAttribute('aria-invalid'));
      };
      form.addEventListener('submit', (e) => {
        clearErrors();
        let firstInvalid = null;
        form.querySelectorAll('input, textarea, select').forEach(field => {
          if(!field.checkValidity()){
            e.preventDefault();
            field.setAttribute('aria-invalid','true');
            const err = document.createElement('div');
            err.className = 'error-text';
            err.textContent = field.validationMessage || 'Please provide a valid value.';
            field.insertAdjacentElement('afterend', err);
            if(!firstInvalid){ firstInvalid = field; }
          }
        });
        if(firstInvalid){ firstInvalid.focus(); }
      });
      form.addEventListener('input', (e) => {
        const field = e.target;
        if(field instanceof HTMLElement && field.matches('[aria-invalid]') && field.checkValidity()){
          field.removeAttribute('aria-invalid');
          const err = field.nextElementSibling;
          if(err && err.classList.contains('error-text')){ err.remove(); }
        }
      });
    });
  };

  const getJobIdFromUrl = () => {
    const id = new URLSearchParams(window.location.search).get('id');
    return id ? Number(id) : null;
  };

  // Render job listing grid
  const renderJobList = () => {
    const grid = document.querySelector('[data-job-grid]');
    if(!grid) return;
    const jobs = load(KEYS.jobs, []);
    grid.innerHTML = jobs.map(job => `
      <article class="job-card">
        <div class="job-header">
          <div>
            <div class="job-company">${job.company}</div>
            <div class="job-location"><i class="fa-solid fa-location-dot"></i> ${job.location}</div>
          </div>
        </div>
        <div class="job-role">${job.title}</div>
        <div class="job-meta">
          <span><i class="fa-solid fa-briefcase"></i> ${job.type}</span>
          <span><i class="fa-regular fa-clock"></i> Posted ${job.posted}</span>
        </div>
        <div class="job-desc">${job.description}</div>
        <div class="job-footer">
          <div class="job-salary">${job.salary}</div>
          <a class="job-apply" href="apply.html?id=${job.id}" aria-label="Apply to ${job.title}">Apply</a>
        </div>
      </article>
    `).join('');
  };

  // Populate job detail page
  const renderJobDetail = () => {
    const wrap = document.querySelector('[data-job-detail]');
    if(!wrap) return;
    const jobId = getJobIdFromUrl();
    const job = load(KEYS.jobs, []).find(j => j.id === jobId);
    if(!job){ wrap.innerHTML = '<p class="subtle">Job not found.</p>'; return; }
    const applyLink = wrap.querySelector('[data-apply-link]');
    if(applyLink) applyLink.href = `apply.html?id=${job.id}`;
    wrap.querySelector('[data-job-title]').textContent = job.title;
    wrap.querySelector('[data-job-company]').textContent = job.company;
    wrap.querySelector('[data-job-location]').textContent = job.location;
    wrap.querySelector('[data-job-salary]').textContent = job.salary;
    wrap.querySelector('[data-job-type]').textContent = job.type;
    wrap.querySelector('[data-job-category]').textContent = job.category;
    wrap.querySelector('[data-job-posted]').textContent = job.posted;
    wrap.querySelector('[data-job-desc]').textContent = job.description;
    wrap.querySelector('[data-job-resp]').innerHTML = job.responsibilities.map(r => `<li>${r}</li>`).join('');
    wrap.querySelector('[data-job-req]').innerHTML = job.requirements.map(r => `<li>${r}</li>`).join('');
  };

  // Register forms (seeker/employer)
  const wireRegisterForms = () => {
    document.querySelectorAll('[data-register-form]').forEach(form => {
      form.addEventListener('submit', (e) => {
        e.preventDefault();
        const role = form.dataset.role;
        const fd = new FormData(form);
        const password = fd.get('password')?.toString() || '';
        const confirm = fd.get('confirmPassword')?.toString() || '';
        if(password !== confirm){ alert('Passwords must match.'); return; }
        const email = (fd.get('email') || fd.get('workEmail') || '').toString().toLowerCase();
        if(!email){ alert('Email is required.'); return; }
        const users = load(KEYS.users, []);
        if(users.some(u => u.email === email)){ alert('An account with this email already exists.'); return; }
        const user = {
          id:`u-${Date.now()}`,
          role,
          email,
          password,
          name: fd.get('fullName') || fd.get('contactName') || fd.get('company') || 'User',
          company: role === 'Employer' ? (fd.get('company') || '') : ''
        };
        users.push(user);
        save(KEYS.users, users);
        setCurrent({ id:user.id, role:user.role, email:user.email, name:user.name, company:user.company });
        alert('Account created. You are now signed in.');
        window.location.href = 'index.html';
      });
    });
  };

  // Login form
  const wireLoginForm = () => {
    const form = document.querySelector('[data-login-form]');
    if(!form) return;
    let selectedRole = 'Seeker';
    const roleButtons = form.querySelectorAll('.toggle-btn');
    roleButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        roleButtons.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        selectedRole = btn.textContent.trim();
      });
    });
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const email = form.querySelector('input[name="email"]')?.value.trim().toLowerCase();
      const password = form.querySelector('input[name="password"]')?.value || '';
      const users = load(KEYS.users, []);
      const user = users.find(u => u.email === email && u.password === password && u.role === selectedRole);
      if(!user){ alert('Invalid credentials or role.'); return; }
      setCurrent({ id:user.id, role:user.role, email:user.email, name:user.name, company:user.company });
      alert(`Welcome back, ${user.name || user.email}!`);
      window.location.href = 'index.html';
    });
  };

  // Apply form (seeker only, prevent duplicates)
  const wireApplyForm = () => {
    const form = document.querySelector('[data-apply-form]');
    if(!form) return;
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const user = getCurrent();
      if(!user || user.role !== 'Seeker'){ alert('Please log in as a Seeker to apply.'); return; }
      const jobId = getJobIdFromUrl();
      if(!jobId){ alert('Missing job id.'); return; }
      const apps = load(KEYS.apps, []);
      if(apps.some(a => a.jobId === jobId && a.userId === user.id)){ alert('You already applied to this job.'); return; }
      const fd = new FormData(form);
      apps.push({
        id:`app-${Date.now()}`,
        jobId,
        userId:user.id,
        cover: fd.get('cover') || '',
        telegram: fd.get('telegram') || '',
        portfolio: fd.get('portfolio') || '',
        createdAt:new Date().toISOString(),
        status:'Pending'
      });
      save(KEYS.apps, apps);
      alert('Application submitted!');
      window.location.href = 'thank-you-apply.html';
    });
  };

  // Show current user + logout in nav
  const renderNavAuth = () => {
    const auth = document.querySelector('.auth-links');
    if(!auth) return;
    const user = getCurrent();
    if(!user) return;
    const profileHref = user.role === 'Employer' ? 'company-profile.html' : 'user-profile.html';
    auth.innerHTML = `
      <a class="subtle" style="font-weight:700;" href="${profileHref}">${user.name || user.email} (${user.role})</a>
      <button class="btn-ghost" style="padding:8px 12px;font-size:0.85rem;" type="button" data-logout>Logout</button>
    `;
    auth.querySelector('[data-logout]')?.addEventListener('click', ()=>{ logout(); window.location.reload(); });
  };

  // Bootstrap
  ensureSeed();
  wireNav();
  wireValidation();
  renderNavAuth();
  renderJobList();
  renderJobDetail();
  wireRegisterForms();
  wireLoginForm();
  wireApplyForm();
})();
