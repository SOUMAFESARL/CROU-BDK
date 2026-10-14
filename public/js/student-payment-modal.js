(() => {
    const dialog = document.getElementById('rent-payment-dialog');
    if (!dialog || dialog.dataset.initialized) return;
    dialog.dataset.initialized = 'true';
    const content = dialog.querySelector('[data-payment-content]');
    const message = dialog.querySelector('[data-payment-message]');
    let requestId = 0, sending = false, lastUrl = '', opener;
    const showMessage = (text, error = false) => {
        message.textContent = text;
        message.setAttribute('role', error ? 'alert' : 'status');
    };
    async function openForm(url) {
        lastUrl = url;
        const id = ++requestId;
        content.replaceChildren();
        showMessage('Chargement de votre loyer…');
        if (!dialog.open) dialog.showModal();
        try {
            const response = await fetch(url, {headers: {Accept: 'application/json'}, credentials: 'same-origin'});
            if (!response.ok || response.redirected) throw new Error('Impossible de charger le formulaire. Vérifiez votre connexion et votre session.');
            const data = await response.json();
            if (id !== requestId || !dialog.open) return;
            content.innerHTML = data.html;
            showMessage('');
            content.querySelector('[name=amount]')?.focus();
        } catch (error) {
            if (id !== requestId || !dialog.open) return;
            showMessage(error.message, true);
            const retry = document.createElement('button');
            retry.type = 'button'; retry.dataset.retryPayment = ''; retry.textContent = 'Réessayer';
            retry.addEventListener('click', () => openForm(lastUrl));
            content.append(retry);
        }
    }
    document.addEventListener('click', event => {
        const button = event.target.closest('[data-payment-form]');
        if (!button || sending) return;
        opener = button;
        openForm(button.dataset.paymentForm);
    });
    dialog.querySelector('[data-close-payment]').addEventListener('click', () => { if (!sending) dialog.close(); });
    dialog.addEventListener('cancel', event => { if (sending) event.preventDefault(); });
    dialog.addEventListener('close', () => { requestId++; opener?.focus(); });
    dialog.addEventListener('submit', async event => {
        event.preventDefault();
        const form = event.target;
        if (sending || !form.reportValidity()) return;
        sending = true;
        const button = form.querySelector('[type=submit]');
        const label = button.textContent;
        button.disabled = true; button.textContent = 'Paiement en cours…';
        showMessage('Transmission de votre demande au réseau…');
        try {
            const response = await fetch(form.action, {method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: {Accept: 'application/json'}});
            if (response.status === 422) {
                const data = await response.json();
                showMessage(Object.values(data.errors || {}).flat().join(' ') || data.message, true);
                return;
            }
            if (response.status === 401 || response.status === 419 || response.redirected) throw new Error('Votre session a expiré. Reconnectez-vous avant de continuer.');
            if (!response.ok) throw new Error('La confirmation est indisponible. Vérifiez le suivi avant de renouveler le paiement.');
            const data = await response.json();
            content.replaceChildren();
            showMessage(data.label);
            if (data.checkout_url) { window.location.assign(data.checkout_url); return; }
            const follow = document.createElement('a');
            follow.href = data.show_url;
            follow.textContent = 'Suivre ce paiement';
            content.append(follow);
            if (data.status === 'pending') showMessage('Demande transmise. Validez le paiement sur votre téléphone, puis consultez son suivi.');
        } catch (error) {
            showMessage(error.message || 'Connexion interrompue. Vérifiez le suivi du paiement avant de réessayer.', true);
        } finally {
            sending = false;
            button.disabled = false; button.textContent = label;
        }
    });
})();
