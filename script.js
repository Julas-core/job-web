// Supabase-first implementation for JobLaunch (auth, jobs, applications)
(async function(){
  const SUPABASE_URL = window.SUPABASE_URL || '';
  const SUPABASE_ANON_KEY = window.SUPABASE_ANON_KEY || '';
  if(!SUPABASE_URL || !SUPABASE_ANON_KEY){
    console.error('Supabase configuration missing. Set window.SUPABASE_URL and window.SUPABASE_ANON_KEY.');
    return;
  }

  const { createClient } = await import('https://cdn.jsdelivr.net/npm/@supabase/supabase-js/+esm');
  const supabase = createClient(SUPABASE_URL, SUPABASE_ANON_KEY);
  const OAUTH_ROLE_KEY = 'jl_oauth_role';

  const getSession = async () => {
    const { data } = await supabase.auth.getSession();
    return data.session;
  };

  const getCurrent = async () => {
    const session = await getSession();
    const user = session?.user;
    if (!user) return null;
    const { data: profile, error } = await supabase
      .from('profiles')
      .select('id, role, name, company, title, city, bio')
      .eq('id', user.id)
      .maybeSingle();
    if (error) { console.warn('profile fetch error', error); return null; }
    if (!profile) {
      const fallbackRole = localStorage.getItem(OAUTH_ROLE_KEY) || 'Seeker';
      const upsertPayload = { id:user.id, role:fallbackRole, name:user.user_metadata?.full_name || user.email, company:'' };
      await supabase.from('profiles').upsert(upsertPayload);
      localStorage.removeItem(OAUTH_ROLE_KEY);
      return { ...upsertPayload, email:user.email };
    }
    localStorage.removeItem(OAUTH_ROLE_KEY);
    return { ...profile, email:user.email };
  };

  const logout = async () => {
    await supabase.auth.signOut();
  };

  // Utilities
  const getJobIdFromUrl = () => {
    const id = new URLSearchParams(window.location.search).get('id');
    return id ? Number(id) : null;
  };

  const functionFetch = async (name, { method='GET', params='', body=null } = {}) => {
    const session = await getSession();
    const token = session?.access_token;
    const url = `${SUPABASE_URL}/functions/v1/${name}${params ? `?${params}` : ''}`;
    const res = await fetch(url, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'apikey': SUPABASE_ANON_KEY,
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
      },
      body: body ? JSON.stringify(body) : null,
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.error || 'Request failed');
    return data;
  };

  // UI helpers
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

  const wireValidation = () => {
    document.querySelectorAll('form[data-validate]').forEach(form => {
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

  // Render job listing grid with optional search query param ?q=
  const renderJobList = async () => {
    const grid = document.querySelector('[data-job-grid]');
    if(!grid) return;
    grid.innerHTML = '<p class="subtle">Loading jobs...</p>';
    const q = new URLSearchParams(window.location.search).get('q') || '';
    try {
      const { data } = await functionFetch('jobs', { params: new URLSearchParams({ q }).toString() });
      const jobs = data || [];
      if (!jobs.length) { grid.innerHTML = '<p class="subtle">No jobs found.</p>'; return; }
      grid.innerHTML = jobs.map(job => `
        <article class="job-card">
          <div class="job-header">
            <div>
              <div class="job-company">${job.company || ''}</div>
              <div class="job-location"><i class="fa-solid fa-location-dot"></i> ${job.location || ''}</div>
            </div>
          </div>
          <div class="job-role">${job.title}</div>
          <div class="job-meta">
            <span><i class="fa-solid fa-briefcase"></i> ${job.type || ''}</span>
            <span><i class="fa-regular fa-clock"></i> ${job.posted_at ? new Date(job.posted_at).toLocaleDateString() : ''}</span>
          </div>
          <div class="job-desc">${job.description || ''}</div>
          <div class="job-footer">
            <div class="job-salary">${job.salary || ''}</div>
            <a class="job-apply" href="apply.html?id=${job.id}" aria-label="Apply to ${job.title}">Apply</a>
          </div>
        </article>
      `).join('');
    } catch (err) {
      console.error(err);
      grid.innerHTML = '<p class="subtle">Failed to load jobs.</p>';
    }
  };

  const renderJobDetail = async () => {
    const wrap = document.querySelector('[data-job-detail]');
    if(!wrap) return;
    const jobId = getJobIdFromUrl();
    if(!jobId){ wrap.innerHTML = '<p class="subtle">Job not found.</p>'; return; }
    wrap.innerHTML = '<p class="subtle">Loading...</p>';
    try {
      const { data: job } = await functionFetch('jobs', { params: new URLSearchParams({ id: String(jobId) }).toString() });
      if(!job){ wrap.innerHTML = '<p class="subtle">Job not found.</p>'; return; }
      const applyLink = wrap.querySelector('[data-apply-link]');
      if(applyLink) applyLink.href = `apply.html?id=${job.id}`;
      wrap.querySelector('[data-job-title]').textContent = job.title;
      wrap.querySelector('[data-job-company]').textContent = job.company || '';
      wrap.querySelector('[data-job-location]').textContent = job.location || '';
      wrap.querySelector('[data-job-salary]').textContent = job.salary || '';
      wrap.querySelector('[data-job-type]').textContent = job.type || '';
      wrap.querySelector('[data-job-category]').textContent = job.category || '';
      wrap.querySelector('[data-job-posted]').textContent = job.posted_at ? new Date(job.posted_at).toLocaleDateString() : '';
      wrap.querySelector('[data-job-desc]').textContent = job.description || '';
      wrap.querySelector('[data-job-resp]').innerHTML = (job.responsibilities || []).map((r) => `<li>${r}</li>`).join('');
      wrap.querySelector('[data-job-req]').innerHTML = (job.requirements || []).map((r) => `<li>${r}</li>`).join('');
    } catch (err) {
      console.error(err);
      wrap.innerHTML = '<p class="subtle">Failed to load job.</p>';
    }
  };

  const wireRegisterForms = () => {
    document.querySelectorAll('[data-register-form]').forEach(form => {
      form.addEventListener('submit', (e) => {
        e.preventDefault();
        (async ()=>{
          const role = form.dataset.role;
          const fd = new FormData(form);
          const password = fd.get('password')?.toString() || '';
          const confirm = fd.get('confirmPassword')?.toString() || '';
          if(password !== confirm){ alert('Passwords must match.'); return; }
          const email = (fd.get('email') || fd.get('workEmail') || '').toString().toLowerCase();
          if(!email){ alert('Email is required.'); return; }

          const { data, error } = await supabase.auth.signUp({ email, password });
          if (error) { alert(error.message); return; }
          const userId = data?.user?.id;
          if (userId) {
            await supabase.from('profiles').upsert({
              id:userId,
              role,
              name: fd.get('fullName') || fd.get('contactName') || fd.get('company') || 'User',
              company: role === 'Employer' ? (fd.get('company') || '') : ''
            });
          }
          alert('Account created. Check email if confirmation is required.');
          window.location.href = 'index.html';
        })();
      });
    });
  };

  const startGoogleOAuth = async (role='Seeker') => {
    localStorage.setItem(OAUTH_ROLE_KEY, role || 'Seeker');
    const isFile = window.location.origin.startsWith('file');
    const redirectTo = isFile ? window.location.href : `${window.location.origin}/index.html`;
    const { error } = await supabase.auth.signInWithOAuth({ provider:'google', options:{ redirectTo, prompt:'select_account' } });
    if (error) alert(error.message);
  };

  const wireGoogleAuthButtons = () => {
    document.querySelectorAll('[data-google-auth]').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        const role = btn.getAttribute('data-role') || 'Seeker';
        startGoogleOAuth(role);
      });
    });
  };

  const wireLoginForm = () => {
    const form = document.querySelector('[data-login-form]');
    if(!form) return;
    let selectedRole = 'Seeker';
    const roleButtons = form.querySelectorAll('.toggle-btn');
    const googleBtn = form.querySelector('[data-google-auth]');
    roleButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        roleButtons.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        selectedRole = btn.textContent.trim();
        if (googleBtn) googleBtn.dataset.role = selectedRole;
      });
    });
    if (googleBtn) googleBtn.dataset.role = selectedRole;
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      (async ()=>{
        const email = form.querySelector('input[name="email"]')?.value.trim().toLowerCase();
        const password = form.querySelector('input[name="password"]')?.value || '';
        const { data, error } = await supabase.auth.signInWithPassword({ email, password });
        if (error) { alert(error.message); return; }
        const user = data?.user;
        if (!user) { alert('Invalid credentials.'); return; }
        const { data: profile } = await supabase.from('profiles').select('role,name,company').eq('id', user.id).single();
        if (profile?.role !== selectedRole) { alert('Role mismatch for this account.'); return; }
        alert(`Welcome back, ${profile?.name || user.email}!`);
        window.location.href = 'index.html';
      })();
    });
  };

  const wireApplyForm = () => {
    const form = document.querySelector('[data-apply-form]');
    if(!form) return;
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      (async ()=>{
        const user = await getCurrent();
        if(!user || user.role !== 'Seeker'){ alert('Please log in as a Seeker to apply.'); return; }
        const jobId = getJobIdFromUrl();
        if(!jobId){ alert('Missing job id.'); return; }
        const fd = new FormData(form);
        try {
          await functionFetch('applications', { method:'POST', body:{
            job_id: jobId,
            cover: fd.get('cover') || '',
            telegram: fd.get('telegram') || '',
            portfolio: fd.get('portfolio') || ''
          }});
          alert('Application submitted!');
          window.location.href = 'thank-you-apply.html';
        } catch (err) {
          const msg = err.message || '';
          const dup = msg.toLowerCase().includes('already applied');
          alert(dup ? 'You already applied to this job.' : msg);
        }
      })();
    });
  };

  const renderNavAuth = () => {
    const auth = document.querySelector('.auth-links');
    if(!auth) return;
    (async ()=>{
      const user = await getCurrent();
      if(!user) return;
      const profileHref = user.role === 'Employer' ? 'company-profile.html' : 'user-profile.html';
      const displayName = user.name || user.email || 'Account';
      auth.innerHTML = `
        <a class="subtle" style="font-weight:700;" href="${profileHref}">${displayName} (${user.role})</a>
        <button class="btn-ghost" style="padding:8px 12px;font-size:0.85rem;" type="button" data-logout>Logout</button>
      `;
      auth.querySelector('[data-logout]')?.addEventListener('click', ()=>{ logout().then(()=>window.location.reload()); });
    })();
  };

  const wireAuthListener = () => {
    supabase.auth.onAuthStateChange(() => {
      renderNavAuth();
    });
  };

  // Employer: create new job
  const wireNewJobForm = () => {
    const form = document.querySelector('[data-new-job-form]');
    if(!form) return;
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      (async ()=>{
        const user = await getCurrent();
        if(!user || user.role !== 'Employer'){ alert('Employer account required.'); return; }
        const fd = new FormData(form);
        const payload = {
          title: fd.get('title'),
          description: fd.get('description'),
          salary: fd.get('salary') || '',
          location: fd.get('location') || '',
          category: fd.get('category') || '',
          type: fd.get('type') || '',
          company: fd.get('company') || user.company || ''
        };
        if(!payload.title || !payload.description){ alert('Title and description are required.'); return; }
        try {
          await functionFetch('jobs', { method:'POST', body: payload });
          alert('Job created.');
          window.location.href = 'company-jobs.html';
        } catch (err) {
          alert(err.message || 'Failed to create job');
        }
      })();
    });
  };

  const renderEmployerJobs = async () => {
    const grid = document.querySelector('[data-employer-jobs]');
    if(!grid) return;
    const user = await getCurrent();
    if(!user || user.role !== 'Employer'){ grid.innerHTML = '<p class="subtle">Employer sign-in required.</p>'; return; }
    grid.innerHTML = '<p class="subtle">Loading jobs...</p>';
    const { data, error } = await supabase.from('jobs').select('*').eq('owner_id', user.id).order('posted_at', { ascending:false });
    if (error) { grid.innerHTML = '<p class="subtle">Failed to load jobs.</p>'; return; }
    if (!data?.length) { grid.innerHTML = '<p class="subtle">No jobs posted yet.</p>'; return; }
    grid.innerHTML = data.map(job => `
      <article class="job-card">
        <div class="job-header">
          <div>
            <div class="job-company">${job.title}</div>
            <div class="job-location"><i class="fa-solid fa-location-dot"></i> ${job.location || ''}</div>
          </div>
          <span style="padding:6px 10px;border-radius:8px;background:rgba(0,199,199,0.18);color:var(--accent-teal);font-weight:800;">${job.type || 'Active'}</span>
        </div>
        <div class="job-role">${job.company || ''}</div>
        <div class="job-meta">
          <span><i class="fa-solid fa-calendar"></i> ${job.posted_at ? new Date(job.posted_at).toLocaleDateString() : ''}</span>
        </div>
        <div class="job-footer" style="gap:10px;flex-wrap:wrap;">
          <a class="job-apply" href="company-applicants.html?job_id=${job.id}">Applicants</a>
          <button class="btn btn-ghost" type="button" data-delete-job="${job.id}">Delete</button>
        </div>
      </article>
    `).join('');
    grid.querySelectorAll('[data-delete-job]').forEach(btn => {
      btn.addEventListener('click', async () => {
        if(!confirm('Delete this job?')) return;
        try {
          await functionFetch('jobs', { method:'DELETE', body:{ id: Number(btn.getAttribute('data-delete-job')) } });
          renderEmployerJobs();
        } catch (err) {
          alert(err.message || 'Failed to delete job');
        }
      });
    });
  };

  const renderEmployerApplicants = async () => {
    const grid = document.querySelector('[data-employer-applicants]');
    if(!grid) return;
    const user = await getCurrent();
    if(!user || user.role !== 'Employer'){ grid.innerHTML = '<p class="subtle">Employer sign-in required.</p>'; return; }
    const jobId = Number(new URLSearchParams(window.location.search).get('job_id'));
    if(!jobId){ grid.innerHTML = '<p class="subtle">Select a job from your list to view applicants.</p>'; return; }
    grid.innerHTML = '<p class="subtle">Loading applicants...</p>';
    try {
      const { data } = await functionFetch('applications', { params: new URLSearchParams({ job_id: String(jobId) }).toString() });
      if (!data?.length) { grid.innerHTML = '<p class="subtle">No applicants yet.</p>'; return; }
      grid.innerHTML = data.map(app => `
        <article class="job-card">
          <div class="job-header">
            <div>
              <div class="job-company">${app.seeker?.name || 'Applicant'}</div>
              <div class="job-location"><i class="fa-solid fa-envelope"></i> ${app.seeker?.city || ''}</div>
            </div>
            <span style="padding:6px 10px;border-radius:8px;background:rgba(0,209,45,0.18);color:var(--accent-green);font-weight:800;">${app.status}</span>
          </div>
          <div class="job-role">Cover: ${app.cover || '—'}</div>
          <div class="job-meta">
            <span><i class="fa-solid fa-calendar"></i> Applied ${new Date(app.created_at).toLocaleDateString()}</span>
            ${app.telegram ? `<span><i class="fa-solid fa-paper-plane"></i> ${app.telegram}</span>` : ''}
            ${app.portfolio ? `<span><i class="fa-solid fa-link"></i> <a href="${app.portfolio}" target="_blank" rel="noreferrer">Portfolio</a></span>` : ''}
          </div>
          <div class="job-footer" style="gap:10px;flex-wrap:wrap;">
            <select data-status-select="${app.id}" class="input" style="max-width:200px;">
              <option ${app.status==='Pending'?'selected':''}>Pending</option>
              <option ${app.status==='Interview Scheduled'?'selected':''}>Interview Scheduled</option>
              <option ${app.status==='Rejected'?'selected':''}>Rejected</option>
              <option ${app.status==='Hired'?'selected':''}>Hired</option>
            </select>
            <button class="btn" type="button" data-update-status="${app.id}">Update</button>
          </div>
        </article>
      `).join('');

      grid.querySelectorAll('[data-update-status]').forEach(btn => {
        btn.addEventListener('click', async () => {
          const appId = Number(btn.getAttribute('data-update-status'));
          const select = grid.querySelector(`[data-status-select="${appId}"]`);
          const status = select?.value;
          try {
            await functionFetch('applications', { method:'PATCH', body:{ application_id: appId, status } });
            alert('Status updated');
          } catch (err) {
            alert(err.message || 'Failed to update status');
          }
        });
      });
    } catch (err) {
      grid.innerHTML = `<p class="subtle">${err.message || 'Failed to load applicants'}</p>`;
    }
  };

  const renderMyApplications = async () => {
    const grid = document.querySelector('[data-my-applications]');
    if(!grid) return;
    const user = await getCurrent();
    if(!user || user.role !== 'Seeker'){ grid.innerHTML = '<p class="subtle">Sign in as a Seeker to view applications.</p>'; return; }
    grid.innerHTML = '<p class="subtle">Loading...</p>';
    try {
      const { data } = await functionFetch('applications', { params: 'mine=1' });
      if (!data?.length) { grid.innerHTML = '<p class="subtle">No applications yet.</p>'; return; }
      grid.innerHTML = data.map(app => `
        <article class="job-card">
          <div class="job-header">
            <div>
              <div class="job-company">${app.jobs?.company || ''}</div>
              <div class="job-location"><i class="fa-solid fa-location-dot"></i> ${app.jobs?.location || ''}</div>
            </div>
            <span style="padding:6px 10px;border-radius:8px;background:rgba(0,209,45,0.18);color:var(--accent-green);font-weight:800;">${app.status}</span>
          </div>
          <div class="job-role">${app.jobs?.title || ''}</div>
          <div class="job-meta">
            <span><i class="fa-solid fa-briefcase"></i> ${app.jobs?.type || ''}</span>
            <span><i class="fa-regular fa-clock"></i> Applied ${app.created_at ? new Date(app.created_at).toLocaleDateString() : ''}</span>
          </div>
          <div class="job-desc">Cover: ${app.cover || '—'}</div>
          <div class="job-footer">
            <div class="job-salary">${app.jobs?.salary || ''}</div>
            <a class="job-apply" href="job-details.html?id=${app.job_id}">View Details</a>
          </div>
        </article>
      `).join('');
    } catch (err) {
      grid.innerHTML = `<p class="subtle">${err.message || 'Failed to load applications'}</p>`;
    }
  };

  // Bootstrap
  wireNav();
  wireValidation();
  renderNavAuth();
  wireAuthListener();
  await renderJobList();
  await renderJobDetail();
  wireRegisterForms();
  wireLoginForm();
  wireApplyForm();
  wireGoogleAuthButtons();
  wireNewJobForm();
  renderEmployerJobs();
  renderEmployerApplicants();
  renderMyApplications();
})();

