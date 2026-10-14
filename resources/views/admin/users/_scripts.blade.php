{{-- resources/views/admin/users/_scripts.blade.php --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {

        // ── Mise à jour du label upload lors de la sélection d'un fichier ──
        const fileInput = document.getElementById('campus-fileInput');
        if (fileInput) {
            fileInput.addEventListener('change', function () {
                const label = document.getElementById('upload-text-label');
                if (label && this.files.length > 0) {
                    label.textContent = this.files[0].name + ' sélectionné';
                }
            });
        }

        // ── Drag & drop sur la zone d'upload ─────────────────────────────
        document.querySelectorAll('.upload-area').forEach(area => {
            area.addEventListener('dragover', function (e) {
                e.preventDefault();
                this.style.borderColor = '#3b82f6';
                this.style.backgroundColor = '#f0f9ff';
            });

            area.addEventListener('dragleave', function () {
                this.style.borderColor = '#d1d5db';
                this.style.backgroundColor = '#f9fafb';
            });

            area.addEventListener('drop', function (e) {
                e.preventDefault();
                this.style.borderColor = '#d1d5db';
                this.style.backgroundColor = '#f9fafb';

                const input = document.getElementById('campus-fileInput');
                if (input && e.dataTransfer.files.length > 0) {
                    input.files = e.dataTransfer.files;
                    const label = document.getElementById('upload-text-label');
                    if (label) label.textContent = e.dataTransfer.files[0].name + ' sélectionné';
                }
            });
        });

        // ── Validation côté client à la soumission ────────────────────────
        const allForms = document.querySelectorAll('form[id^="form-"]');
        allForms.forEach(form => {
            form.addEventListener('submit', function (e) {
                let isValid = true;

                this.querySelectorAll('[required]').forEach(input => {
                    const isEmpty = input.tagName === 'SELECT'
                        ? !input.value
                        : !input.value.trim();

                    if (isEmpty) {
                        isValid = false;
                        input.classList.add('is-invalid');

                        // Ajouter message d'erreur si absent
                        let next = input.nextElementSibling;
                        if (!next || !next.classList.contains('error-message')) {
                            const msg = document.createElement('div');
                            msg.className = 'error-message text-danger mt-1';
                            msg.style.fontSize = '12px';
                            msg.textContent = 'Ce champ est requis';
                            input.parentNode.appendChild(msg);
                        }
                    } else {
                        input.classList.remove('is-invalid');
                        const next = input.nextElementSibling;
                        if (next && next.classList.contains('error-message')) next.remove();
                    }
                });

                if (!isValid) { e.preventDefault(); return; }

                // Loader sur le bouton submit
                const btn = this.querySelector('button[type="submit"]');
                if (btn) {
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi en cours...';
                    btn.disabled = true;
                }
            });
        });
    });

    // ── Réinitialisation du formulaire ────────────────────────────────────
    function resetForm(formId) {
        const form = document.getElementById(formId);
        if (!form) return;

        form.reset();

        form.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
            const next = el.nextElementSibling;
            if (next && next.classList.contains('error-message')) next.remove();
        });

        const label = document.getElementById('upload-text-label');
        if (label) label.textContent = 'Cliquez pour ajouter une photo (PNG, JPEG, JPG)';
    }
</script>