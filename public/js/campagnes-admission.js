/*
 * Pages Blade « ca-page » (équipements, allocations…) : ouverture et fermeture des fenêtres <dialog>
 * et des tiroirs latéraux. La page des campagnes est passée en React (Admin/Hebergement/Campagnes).
 */
(function () {
    'use strict';

    // --- Fenêtres modales ---------------------------------------------------
    function openDialog(id) {
        var dialog = document.getElementById(id);
        if (dialog && typeof dialog.showModal === 'function' && !dialog.open) dialog.showModal();
    }

    // Les tiroirs (.ca-drawer) glissent vers la droite avant de se fermer.
    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    function closeDialog(dialog) {
        if (!dialog.open || dialog.classList.contains('is-closing')) return;
        if (!dialog.classList.contains('ca-drawer') || reduceMotion) {
            dialog.close();
            return;
        }
        dialog.classList.add('is-closing');
        var finished = false;
        var done = function () {
            if (finished) return;
            finished = true;
            dialog.removeEventListener('animationend', done);
            dialog.classList.remove('is-closing');
            dialog.close();
        };
        dialog.addEventListener('animationend', done);
        setTimeout(done, 300);
    }

    document.addEventListener('cancel', function (event) {
        if (event.target.classList && event.target.classList.contains('ca-drawer')) {
            event.preventDefault();
            closeDialog(event.target);
        }
    }, true);

    document.addEventListener('click', function (event) {
        var opener = event.target.closest('[data-ca-open]');
        if (opener) {
            openDialog(opener.getAttribute('data-ca-open'));
            return;
        }
        var closer = event.target.closest('[data-ca-close]');
        if (closer) {
            var dialog = closer.closest('dialog');
            if (dialog) closeDialog(dialog);
            return;
        }
        // Clic sur le fond de la fenêtre : fermeture.
        if (event.target.tagName === 'DIALOG' && event.target.classList.contains('ca-dialog')) {
            var rect = event.target.getBoundingClientRect();
            var inside = event.clientX >= rect.left && event.clientX <= rect.right
                && event.clientY >= rect.top && event.clientY <= rect.bottom;
            if (!inside) closeDialog(event.target);
        }
    });

    document.querySelectorAll('dialog[data-ca-autoopen]').forEach(function (dialog) {
        openDialog(dialog.id);
    });
})();
