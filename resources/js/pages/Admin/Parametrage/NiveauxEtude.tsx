import { ArrowDown, ArrowUp, GraduationCap } from 'lucide-react';
import { useState } from 'react';
import { ICON } from '@/components/layout/app-sidebar';
import { ReferentielPage, type ChampReferentiel, type ElementReferentiel } from '@/components/parametrage-hebergement/referentiel-page';
import { envoyer } from '@/lib/inertia-form';
import { nombre } from '@/lib/text';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types/global';

type Niveau = Omit<ElementReferentiel, 'urls'> & {
    abreviation: string | null;
    demandes: number;
    /** Position dans l'ordre complet (1 = affiché en premier dans les formulaires). */
    rang: number;
    premier: boolean;
    dernier: boolean;
    urls: ElementReferentiel['urls'] & { deplacer: string | null };
};

type Props = {
    elements: Paginated<Niveau>;
    stats: { total: number; utilises: number; inutilises: number };
    filters: { q: string };
    urls: { index: string; store: string | null };
};

const CHAMPS: ChampReferentiel[] = [
    { cle: 'name', label: 'Nom', max: 100, requis: 'Renseignez le nom du niveau d’étude.', placeholder: 'Ex. Licence 1' },
    { cle: 'abreviation', label: 'Abréviation', max: 50, placeholder: 'Ex. L1', hint: 'Facultative : sigle affiché dans les listes.' },
    { cle: 'description', label: 'Description', type: 'zone', max: 2000, placeholder: 'Description du niveau d’étude' },
];

/** Niveaux d'étude (remplace `parametrage/niveau_etude/index.blade.php`) ; l'ordre réglé par les flèches est celui des formulaires de demande. */
export default function NiveauxEtude({ elements, stats, filters, urls }: Props) {
    const [enCours, setEnCours] = useState<string | null>(null);

    const deplacer = async (n: Niveau, sens: 'haut' | 'bas') => {
        if (!n.urls.deplacer || enCours) return;
        setEnCours(n.id);
        await envoyer(n.urls.deplacer, { sens });
        setEnCours(null);
    };

    return (
        <ReferentielPage
            titre="Niveaux d’étude"
            description="Cycles et niveaux proposés dans les demandes de logement (niveau actuel et précédent)."
            singulier="niveau d’étude"
            un="un niveau d’étude"
            champs={CHAMPS}
            elements={elements}
            stats={stats}
            kpis={{ total: 'Niveaux enregistrés', utilises: 'Cités dans des demandes', inutilises: 'Sans demande' }}
            filters={filters}
            urls={urls}
            recherche="Rechercher un niveau, un sigle ou une description"
            icone={<GraduationCap {...ICON} />}
            usage={(n) => ({ total: n.demandes, libelle: 'demande(s) de logement' })}
            ajoutDirect
            colonnesAvant={[
                {
                    key: 'ordre',
                    header: 'Ordre',
                    className: 'w-px whitespace-nowrap',
                    cell: (n) => (
                        <span className="flex items-center gap-1">
                            <span className="w-6 text-center text-sm text-muted tabular-nums">{n.rang}</span>
                            {n.urls.deplacer && (
                                <>
                                    <Fleche label={`Monter « ${n.name} »`} icone={ArrowUp} desactive={n.premier || enCours !== null} onClick={() => void deplacer(n, 'haut')} />
                                    <Fleche label={`Descendre « ${n.name} »`} icone={ArrowDown} desactive={n.dernier || enCours !== null} onClick={() => void deplacer(n, 'bas')} />
                                </>
                            )}
                        </span>
                    ),
                },
            ]}
            conseilSuppression="Un niveau cité dans des demandes ne peut pas être supprimé : renommez-le plutôt."
            colonnes={[
                {
                    key: 'abreviation',
                    header: 'Abréviation',
                    className: 'whitespace-nowrap',
                    cell: (n) => (n.abreviation ? <span className="text-gray-900">{n.abreviation}</span> : <span className="text-gray-400">—</span>),
                },
                {
                    key: 'usage',
                    header: 'Demandes',
                    className: 'whitespace-nowrap text-sm',
                    cell: (n) => (n.demandes === 0 ? <span className="text-muted">Aucune</span> : <span className="text-gray-900">{nombre(n.demandes)} demande(s)</span>),
                },
            ]}
        />
    );
}

function Fleche({ label, icone: Icone, desactive, onClick }: { label: string; icone: typeof ArrowUp; desactive: boolean; onClick: () => void }) {
    return (
        <button
            type="button"
            aria-label={label}
            title={label}
            disabled={desactive}
            onClick={onClick}
            className={cn(
                'inline-flex size-8 items-center justify-center rounded-brand text-primary transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-secondary',
                desactive ? 'cursor-not-allowed opacity-30' : 'cursor-pointer hover:bg-soft',
            )}
        >
            <Icone size={18} strokeWidth={1.25} aria-hidden />
        </button>
    );
}
