(function(){
  function initNav(){
    const toggles = document.querySelectorAll('.nav-toggle');
    toggles.forEach(btn => {
      const drawer = btn.parentElement.querySelector('.nav-drawer');
      btn.addEventListener('click', function(){
        const expanded = this.getAttribute('aria-expanded') === 'true';
        this.setAttribute('aria-expanded', String(!expanded));
        if(drawer) drawer.classList.toggle('open');
        document.body.classList.toggle('nav-open');
      });
    });

    // Close any open drawer when clicking outside
    document.addEventListener('click', function(e){
      toggles.forEach(btn => {
        const drawer = btn.parentElement.querySelector('.nav-drawer');
        if(!drawer) return;
        if (!drawer.contains(e.target) && !btn.contains(e.target) && drawer.classList.contains('open')) {
          drawer.classList.remove('open');
          btn.setAttribute('aria-expanded', 'false');
          document.body.classList.remove('nav-open');
        }
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initNav);
  } else {
    initNav();
  }
})();