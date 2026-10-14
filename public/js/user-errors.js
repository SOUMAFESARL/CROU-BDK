(() => {
    'use strict';
    if (window.userErrorsInitialized) return;
    window.userErrorsInitialized = true;
    let notice;
    let actionUntil = 0;
    const markAction = () => { actionUntil = Date.now() + 30000; };
    // Une erreur de widget au chargement ne signifie pas que la connexion
    // ou la navigation a échoué. Les formulaires gardent leurs messages serveur.
    document.addEventListener('submit', markAction, true);
    document.addEventListener('click', event => {
        if (!(event.target instanceof Element)) return;
        const control = event.target.closest('button, input[type="submit"], [data-user-action]');
        if (control && !control.closest('a[href]') && !control.closest('[role="alert"]')) markAction();
    }, true);
    window.addEventListener('pageshow', () => { actionUntil = 0; });
    const actionPending = () => Date.now() < actionUntil;
    const notify = () => {
        const message = navigator.onLine === false
            ? 'La connexion est interrompue. Vérifiez votre connexion avant de poursuivre.'
            : 'Un problème a interrompu cette action. Vérifiez le résultat avant de réessayer ou d’actualiser la page.';
        if (notice) { notice.querySelector('p').textContent = message; return; }
        notice = document.createElement('div');
        notice.setAttribute('role', 'alert');
        notice.style.cssText = 'position:fixed;bottom:20px;right:20px;left:20px;max-width:640px;margin-left:auto;z-index:2147483647;background:#fff7ed;color:#7c2d12;border:1px solid #fed7aa;border-radius:12px;padding:16px;box-shadow:0 4px 20px #0002;font:15px/1.5 Arial,sans-serif;display:flex;align-items:start;gap:12px';
        const text = document.createElement('p');
        text.style.margin = '0';text.textContent = message;
        const close = document.createElement('button');
        close.type = 'button';close.textContent = 'Fermer';
        close.style.cssText = 'margin-left:auto;padding:6px 10px;border:1px solid #fed7aa;border-radius:6px;background:white;color:#7c2d12;cursor:pointer';
        close.addEventListener('click', () => { notice.remove(); notice = null; });
        notice.append(text, close);document.body.append(notice);
    };
    // Keep developer diagnostics in the console; never display raw exception text.
    window.addEventListener('error', event => { if (event.error && actionPending()) notify(); });
    window.addEventListener('unhandledrejection', event => { if (event.reason?.name !== 'AbortError' && actionPending()) notify(); });
})();
