import frLocale from '@fullcalendar/core/locales/fr';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import FullCalendar from '@fullcalendar/react';
import { CalendarDays } from 'lucide-react';
import { useEffect, useMemo, useRef, useState, type CSSProperties } from 'react';
import { jourIso, STATUTS_DEMANDE, StatutDemandeBadge, type Demande } from '@/components/departs/shared';
import { ICON } from '@/components/layout/app-sidebar';
import { dateFr } from '@/lib/text';

/** Variables de thème FullCalendar aux couleurs de la charte. */
const THEME = {
    '--fc-border-color': '#E6E9E2',
    '--fc-today-bg-color': '#EBF8DE',
    '--fc-button-bg-color': '#FFFFFF',
    '--fc-button-border-color': '#D1D5DB',
    '--fc-button-text-color': '#54595F',
    '--fc-button-hover-bg-color': '#EBF8DE',
    '--fc-button-hover-border-color': '#86BB46',
    '--fc-button-active-bg-color': '#006400',
    '--fc-button-active-border-color': '#006400',
    '--fc-page-bg-color': '#FFFFFF',
    '--fc-neutral-bg-color': '#F8F8F8',
} as CSSProperties;

type Props = {
    demandes: Demande[];
    /** Date filtrée (`aaaa-mm-jj`) : le calendrier s'y place et la sélectionne. */
    date: string;
    onOuvrir: (demande: Demande) => void;
};

/** Calendrier mensuel des sorties prévues (remplace `public/js/departure-calendar.js`) et agenda de la journée choisie. */
export function CalendrierSorties({ demandes, date, onOuvrir }: Props) {
    const calendrier = useRef<FullCalendar>(null);
    const [jour, setJour] = useState(date || jourIso());

    useEffect(() => {
        if (!date) return;
        setJour(date);
        calendrier.current?.getApi().gotoDate(date);
    }, [date]);

    const evenements = useMemo(
        () =>
            demandes
                .filter((d) => d.date_sortie)
                .map((d) => ({
                    id: d.id,
                    title: `${d.etudiant}${d.chambre ? ` · ch. ${d.chambre}` : ''}`,
                    start: d.date_sortie as string,
                    allDay: true,
                    backgroundColor: STATUTS_DEMANDE[d.statut].couleur,
                    borderColor: STATUTS_DEMANDE[d.statut].couleur,
                    textColor: d.statut === 'en_attente' ? '#1F2328' : '#FFFFFF',
                })),
        [demandes],
    );

    const duJour = demandes.filter((d) => d.date_sortie === jour);

    return (
        <div className="grid gap-4 xl:grid-cols-[minmax(0,1fr)_320px]">
            <div className="calendrier-sorties overflow-x-auto rounded-brand border bg-white p-4" style={THEME}>
                <div className="min-w-[640px]">
                    <FullCalendar
                        ref={calendrier}
                        plugins={[dayGridPlugin, interactionPlugin]}
                        initialView="dayGridMonth"
                        initialDate={date || undefined}
                        locale={frLocale}
                        height="auto"
                        fixedWeekCount={false}
                        dayMaxEvents={2}
                        headerToolbar={{ left: 'title', center: '', right: 'prev,today,next' }}
                        events={evenements}
                        dateClick={(info) => setJour(info.dateStr)}
                        eventClick={(info) => {
                            const demande = demandes.find((d) => d.id === info.event.id);
                            if (demande) onOuvrir(demande);
                        }}
                        dayCellClassNames={(arg) => (jourIso(arg.date) === jour ? ['jour-choisi'] : [])}
                    />
                </div>
            </div>

            <aside className="h-fit rounded-brand border bg-white" aria-live="polite">
                <h3 className="flex items-center gap-2 border-b px-4 py-3 text-sm font-medium text-gray-900">
                    <CalendarDays {...ICON} className="text-primary" aria-hidden /> Sorties du {dateFr(jour)}
                </h3>
                {duJour.length === 0 ? (
                    <p className="px-4 py-6 text-center text-sm text-muted">Aucune sortie prévue ce jour avec les filtres actuels.</p>
                ) : (
                    <ul className="divide-y">
                        {duJour.map((d) => (
                            <li key={d.id}>
                                <button type="button" onClick={() => onOuvrir(d)} className="flex w-full cursor-pointer items-start justify-between gap-2 px-4 py-3 text-left hover:bg-surface focus-visible:bg-soft focus-visible:outline-none">
                                    <span className="min-w-0">
                                        <span className="block truncate text-sm text-gray-900">{d.etudiant}</span>
                                        <span className="block truncate text-xs text-muted">{[d.matricule, d.chambre && `Chambre ${d.chambre}`, d.cite].filter(Boolean).join(' · ')}</span>
                                    </span>
                                    <StatutDemandeBadge statut={d.statut} />
                                </button>
                            </li>
                        ))}
                    </ul>
                )}
                <ul className="flex flex-wrap gap-x-4 gap-y-1 border-t px-4 py-3 text-xs text-muted" aria-label="Légende">
                    {Object.values(STATUTS_DEMANDE).map((s) => (
                        <li key={s.label} className="flex items-center gap-1.5">
                            <span className="size-2.5 rounded-sm" style={{ backgroundColor: s.couleur }} aria-hidden />
                            {s.label}
                        </li>
                    ))}
                </ul>
            </aside>
        </div>
    );
}
