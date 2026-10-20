import { GraduationCap } from 'lucide-react';
import { ICON } from '@/components/layout/app-sidebar';
import { ReferentielPage, type ChampReferentiel, type ElementReferentiel } from '@/components/parametrage-hebergement/referentiel-page';
import { nombre } from '@/lib/text';
import type { Paginated } from '@/types/global';

type Niveau = ElementReferentiel & { abreviation: string | null; demandes: number };

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

/** Niveaux d'étude (remplace `parametrage/niveau_etude/index.blade.php`) ; actions `store`, `update`, `delete` inchangées. */
export default function NiveauxEtude({ elements, stats, filters, urls }: Props) {
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
