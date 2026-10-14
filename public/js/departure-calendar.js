(() => {
    'use strict';
    window.departureSearchText = value => String(value ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim().toLocaleLowerCase('fr');
    document.addEventListener('DOMContentLoaded', () => {
        const grid = document.getElementById('departureGrid');
        if (!grid) return;
        const events = JSON.parse(document.getElementById('departureEvents').textContent);
        const labels = {en_attente: 'En attente', approuvee: 'Approuvée', cloturee: 'Clôturée', refusee: 'Refusée / annulée'};
        const today = new Date();
        let month = new Date(today.getFullYear(), today.getMonth(), 1);
        let selected = '';
        const key = date => [date.getFullYear(), String(date.getMonth() + 1).padStart(2, '0'), String(date.getDate()).padStart(2, '0')].join('-');
        const filters = () => Object.fromEntries(['searchStudent', 'filterStatus', 'filterDate', 'filterCite'].map(id => [id, document.getElementById(id)?.value || '']));
        function filtered() {
            const f = filters();
            return events.filter(e => (!f.searchStudent || window.departureSearchText(e.name + ' ' + e.matricule + ' ' + e.room).includes(window.departureSearchText(f.searchStudent)))
                && (!f.filterStatus || e.status === f.filterStatus) && (!f.filterDate || e.date === f.filterDate) && (!f.filterCite || e.cite === f.filterCite));
        }
        function agenda(date) {
            selected = date;
            const panel = document.getElementById('departureAgenda');
            panel.replaceChildren();
            const title = document.createElement('h6');
            title.textContent = 'Sorties du ' + new Date(date + 'T12:00:00').toLocaleDateString('fr-FR');
            panel.append(title);
            const list = filtered().filter(e => e.date === date);
            if (!list.length) {
                const empty = document.createElement('p');
                empty.textContent = 'Aucune sortie prévue pour cette journée avec les filtres actuels.';
                panel.append(empty);
            }
            list.forEach(e => {
                const item = document.createElement('p');
                item.textContent = e.name + (e.matricule ? ' · ' + e.matricule : '') + ' · Chambre ' + e.room + ' · ' + labels[e.status];
                panel.append(item);
            });
        }
        function render() {
            grid.replaceChildren();
            document.getElementById('departureMonth').textContent = month.toLocaleDateString('fr-FR', {month:'long', year:'numeric'});
            ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'].forEach(label => {
                const heading = document.createElement('div'); heading.className = 'departure-heading'; heading.textContent = label; grid.append(heading);
            });
            const start = new Date(month);
            start.setDate(1 - ((start.getDay() + 6) % 7));
            const visible = filtered();
            for (let i = 0; i < 42; i++) {
                const date = new Date(start); date.setDate(start.getDate() + i);
                const id = key(date), list = visible.filter(e => e.date === id);
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'departure-day' + (date.getMonth() !== month.getMonth() ? ' outside' : '') + (id === key(today) ? ' today' : '');
                button.setAttribute('aria-pressed', String(selected === id));
                button.setAttribute('aria-label', date.toLocaleDateString('fr-FR') + ', ' + list.length + ' sortie(s)');
                button.textContent = date.getDate();
                list.slice(0, 2).forEach(e => { const line = document.createElement('small'); line.textContent = e.name + ' · ' + labels[e.status]; button.append(line); });
                if (list.length > 2) { const more = document.createElement('small'); more.textContent = '+ ' + (list.length - 2) + ' sortie(s)'; button.append(more); }
                button.addEventListener('click', () => { agenda(id); render(); });
                grid.append(button);
            }
        }
        function refresh() {
            const f = filters();
            if (f.filterDate) { month = new Date(f.filterDate + 'T12:00:00'); month.setDate(1); selected = f.filterDate; }
            const rows = [...document.querySelectorAll('#inspectionHistory tr[data-date]')];
            let count = 0;
            rows.forEach(row => {
                const visible = (!f.searchStudent || window.departureSearchText(row.textContent).includes(window.departureSearchText(f.searchStudent)))
                    && (!f.filterDate || row.dataset.date === f.filterDate) && (!f.filterCite || row.dataset.cite === f.filterCite);
                row.hidden = !visible; if (visible) count++;
            });
            const empty = document.getElementById('inspectionHistoryEmpty'); if (empty) empty.hidden = count > 0;
            let departures = 0;
            document.querySelectorAll('tr[data-history-status]').forEach(row => {
                const visible = (!f.searchStudent || window.departureSearchText(row.textContent).includes(window.departureSearchText(f.searchStudent)))
                    && (!f.filterStatus || row.dataset.historyStatus === f.filterStatus)
                    && (!f.filterDate || row.dataset.historyDate === f.filterDate)
                    && (!f.filterCite || row.dataset.historyCite === f.filterCite);
                row.hidden = !visible; if (visible) departures++;
            });
            const departureEmpty = document.getElementById('departureHistoryEmpty');
            if (departureEmpty) departureEmpty.hidden = departures > 0;
            render(); if (selected) agenda(selected);
        }
        document.getElementById('departurePrevious').addEventListener('click', () => { month.setMonth(month.getMonth() - 1); render(); });
        document.getElementById('departureNext').addEventListener('click', () => { month.setMonth(month.getMonth() + 1); render(); });
        document.getElementById('departureToday').addEventListener('click', () => { month = new Date(today.getFullYear(), today.getMonth(), 1); agenda(key(today)); render(); });
        window.departureCalendar = {refresh};
        refresh();
    });
})();