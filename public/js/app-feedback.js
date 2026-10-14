(() => {
    'use strict';
    if (window.appFeedbackInitialized) return;
    window.appFeedbackInitialized = true;
    const status = document.getElementById('app-operation-status');
    const pending = new Map();
    const announce = message => {
        if (!status) return;
        status.textContent = message;
        status.hidden = !message;
    };
    window.appNotify = announce;
    function reset() {
        pending.forEach((state, form) => {
            clearTimeout(state.timer);
            form.removeAttribute('aria-busy');
            if (state.button) {
                state.button.removeAttribute('aria-disabled');
                state.button.classList.remove('app-button-loading');
            }
        });
        pending.clear();
        announce('');
    }
    document.addEventListener('click', event => {
        const close = event.target.closest('[data-dismiss-notice]');
        if (close) close.closest('.app-notice').remove();
        const error = event.target.closest('[data-error-field]');
        if (!error) return;
        event.preventDefault();
        const name = error.dataset.errorField;
        const bracketName = name.replace(/\.([^.]*)/g, '[$1]');
        const field = [...document.querySelectorAll('input,select,textarea')].find(el => el.name === name || el.name === bracketName);
        if (field) {
            field.setAttribute('aria-invalid', 'true');
            field.scrollIntoView({block: 'center'});
            field.focus({preventScroll: true});
        }
    });
    document.addEventListener('submit', event => {
        const form = event.target;
        if (pending.has(form)) { event.preventDefault(); return; }
        // Run after page handlers, so AJAX forms keep their own lifecycle.
        queueMicrotask(() => {
            if (event.defaultPrevented || form.target === '_blank' || form.hasAttribute('data-no-loading')) return;
            const button = event.submitter;
            form.setAttribute('aria-busy', 'true');
            if (button) {
                button.setAttribute('aria-disabled', 'true');
                button.classList.add('app-button-loading');
            }
            announce(form.method.toLowerCase() === 'get' ? 'Chargement des résultats…' : 'Envoi en cours, veuillez patienter…');
            const timer = setTimeout(() => {
                reset();
                announce('La réponse prend plus de temps que prévu. Vérifiez le résultat avant de renouveler votre action.');
            }, 30000);
            pending.set(form, {button, timer});
        });
    });
    window.addEventListener('pageshow', reset);
    const network = document.getElementById('app-network-status');
    const updateNetwork = () => { if (network) network.hidden = navigator.onLine; };
    window.addEventListener('online', updateNetwork);
    window.addEventListener('offline', updateNetwork);
    updateNetwork();
    document.querySelectorAll('.pcoded-navbar a[href], .admin-shell-nav a[href]').forEach(link => {
        const url = new URL(link.href, location.href);
        if (url.origin === location.origin && url.pathname === location.pathname && url.search === location.search) {
            link.setAttribute('aria-current', 'page');
        }
    });
    document.getElementById('app-validation-errors')?.focus({preventScroll: true});
})();
