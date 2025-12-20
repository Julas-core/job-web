// Shared client-side helpers for JobLaunch
(function(){
  // Mobile navigation toggle -> slide-in drawer
  const navToggle = document.querySelector('.nav-toggle');
  if(navToggle){
    const closeNav = () => {
      document.body.classList.remove('nav-open');
      navToggle.setAttribute('aria-expanded','false');
    };

    navToggle.addEventListener('click', ()=>{
      const open = document.body.classList.toggle('nav-open');
      navToggle.setAttribute('aria-expanded', String(open));
    });

    document.addEventListener('keydown', (e)=>{
      if(e.key === 'Escape'){ closeNav(); }
    });

    document.querySelectorAll('.nav-links a, .auth-links a').forEach(link => {
      link.addEventListener('click', closeNav);
    });
  }

  // Simple inline validation for forms marked data-validate
  const forms = document.querySelectorAll('form[data-validate]');
  forms.forEach(form => {
    form.setAttribute('novalidate', 'true');

    const clearErrors = () => {
      form.querySelectorAll('.error-text').forEach(node => node.remove());
      form.querySelectorAll('[aria-invalid="true"]').forEach(field => field.removeAttribute('aria-invalid'));
    };

    form.addEventListener('submit', (e) => {
      clearErrors();
      let firstInvalid = null;
      const fields = form.querySelectorAll('input, textarea, select');
      fields.forEach(field => {
        if(!field.checkValidity()){
          e.preventDefault();
          field.setAttribute('aria-invalid','true');
          const msg = field.validationMessage || 'Please provide a valid value.';
          const err = document.createElement('div');
          err.className = 'error-text';
          err.textContent = msg;
          field.insertAdjacentElement('afterend', err);
          if(!firstInvalid){ firstInvalid = field; }
        }
      });
      if(firstInvalid){ firstInvalid.focus(); }
    });

    form.addEventListener('input', (e) => {
      const field = e.target;
      if(!(field instanceof HTMLElement)){ return; }
      if(field.matches('[aria-invalid]')){
        if(field.checkValidity()){
          field.removeAttribute('aria-invalid');
          const err = field.nextElementSibling;
          if(err && err.classList.contains('error-text')){ err.remove(); }
        }
      }
    });
  });
})();
