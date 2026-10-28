(() => {
    'use strict';
    const forms = document.querySelectorAll('[data-simple-upload]');
    forms.forEach(form => {
        const button = form.querySelector('button[type="submit"]');
        const status = form.querySelector('[data-simple-status]');
        const initial = button.innerHTML;
        const reset = () => {
            button.disabled = false;
            button.innerHTML = initial;
            status.hidden = true;
            form.removeAttribute('aria-busy');
        };
        form.addEventListener('submit', event => {
            if (form.getAttribute('aria-busy') === 'true') { event.preventDefault(); return; }
            button.disabled = true;
            button.innerHTML = '<i class="fa fa-spinner fa-spin" aria-hidden="true"></i> Memproses…';
            status.textContent = 'Memproses file…';
            status.hidden = false;
            form.setAttribute('aria-busy', 'true');
        });
        window.addEventListener('pageshow', reset);
    });
})();
