window.EdlPhotos = {
    pending: 0,
    mount(room, photos = [], readonly = false, element = false) {
        const zone = document.createElement('div');
        zone.className = 'edl-photos';
        zone.style.cssText = 'display:flex;flex-wrap:wrap;gap:10px;padding:12px';
        room.appendChild(zone);
        const add = (value, url) => {
            const figure = document.createElement('div');
            figure.className = 'edl-photo';
            figure.dataset.photo = value;
            const link = document.createElement('a');
            link.href = url;
            link.target = '_blank';
            link.rel = 'noopener';
            const img = document.createElement('img');
            img.src = url;
            img.alt = element ? 'Photo de l’élément' : 'Photo de la pièce';
            img.style.cssText = 'width:100px;height:100px;object-fit:cover;border-radius:6px';
            link.appendChild(img);
            figure.appendChild(link);
            if (!readonly) {
                const remove = document.createElement('button');
                remove.type = 'button';
                remove.textContent = 'Retirer';
                remove.onclick = () => figure.remove();
                figure.appendChild(remove);
            }
            zone.insertBefore(figure, zone.querySelector('.photo-add'));
        };
        photos.forEach(photo => {
            const path = photo.path || photo.chemin_fichier;
            if (path) add(path, photo.url || `${window.location.origin}/storage/${path}`);
        });
        {
            const hint = document.createElement('small');
            hint.className = 'edl-photo-heading'; hint.textContent = element ? 'Photo facultative' : 'Photos générales de la pièce (facultatives)';
            hint.style.cssText = 'display:block;width:100%';
            zone.prepend(hint);
        }
        if (readonly) { if (!zone.querySelector('.edl-photo')) { const empty = document.createElement('span'); empty.className = 'edl-photo-empty'; empty.textContent = 'Aucune photo enregistrée'; zone.appendChild(empty); } return; }
        for (const camera of [true, false]) {
            const label = document.createElement('label');
            label.className = 'btn btn-outline-secondary photo-add';
            label.style.cssText = 'width:auto;height:auto;padding:10px;cursor:pointer';
            label.appendChild(document.createTextNode(camera ? 'Prendre une photo' : (element ? 'Choisir une photo' : 'Ajouter des photos')));
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'image/jpeg,image/png,image/webp';
            input.hidden = true;
            if (camera) input.setAttribute('capture', 'environment');
            else input.multiple = !element;
            input.onchange = async () => {
                this.pending++;
                try {
                    for (const file of input.files) {
                        if (!element && zone.querySelectorAll('.edl-photo').length >= 6) throw new Error('Maximum 6 photos par pièce.');
                        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 20 * 1024 * 1024) {
                            throw new Error('Choisissez une photo JPEG, PNG ou WebP de moins de 20 Mo.');
                        }
                        const url = URL.createObjectURL(file);
                        try {
                            const img = new Image();
                            img.src = url;
                            await img.decode();
                            const ratio = Math.min(1, 1600 / Math.max(img.width, img.height));
                            const canvas = document.createElement('canvas');
                            canvas.width = Math.max(1, Math.round(img.width * ratio));
                            canvas.height = Math.max(1, Math.round(img.height * ratio));
                            canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
                            const data = canvas.toDataURL('image/jpeg', 0.8);
                            if (element) zone.querySelectorAll('.edl-photo').forEach(photo => photo.remove());
                            add(data, data);
                        } finally { URL.revokeObjectURL(url); }
                    }
                } catch (error) { alert(error.message || 'Impossible de lire cette photo.'); }
                finally { input.value = ''; this.pending--; }
            };
            label.appendChild(input);
            zone.appendChild(label);
        }
    },
    read(room) { return [...room.querySelectorAll(':scope > .edl-photos > .edl-photo')].map(photo => photo.dataset.photo); },
    mountElement(row, saved = {}, readonly = false) {
        const cell = document.createElement('td');
        cell.className = 'edl-element-photo';
        row.appendChild(cell);
        this.mount(cell, saved.photo ? [{path: saved.photo, url: saved.photo_url}] : [], readonly, true);
    },
    readElement(row) {
        const cell = row.querySelector('.edl-element-photo');
        return cell ? this.read(cell)[0] || null : null;
    },
    ready() {
        if (this.pending) { alert('Veuillez attendre la préparation des photos.'); return false; }
        return true;
    }
};
