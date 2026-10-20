import { Flag } from 'lucide-react';
import { ICON } from '@/components/layout/app-sidebar';
import { ReferentielPage, type ChampReferentiel, type ElementReferentiel } from '@/components/parametrage-hebergement/referentiel-page';
import { nombre } from '@/lib/text';
import type { Paginated } from '@/types/global';

type Pays = ElementReferentiel & { code: string | null; etudiants: number; campus: number };

type Props = {
    elements: Paginated<Pays>;
    stats: { total: number; utilises: number; inutilises: number };
    filters: { q: string };
    urls: { index: string; store: string | null };
};

const CHAMPS: ChampReferentiel[] = [
    { cle: 'name', label: 'Nom', max: 225, requis: 'Renseignez le nom du pays.', placeholder: 'Ex. Côte d’Ivoire' },
    { cle: 'code', label: 'Code', max: 150, placeholder: 'Ex. CI', hint: 'Facultatif : code court du pays (ISO de préférence).' },
];

/** Pays (remplace `parametrage/pays/index.blade.php`) ; actions `store`, `update`, `delete` inchangées. */
export default function Pays({ elements, stats, filters, urls }: Props) {
    return (
        <ReferentielPage
            titre="Pays"
            description="Pays d’origine des étudiants et de rattachement des campus."
            singulier="pays"
            un="un pays"
            champs={CHAMPS}
            elements={elements}
            stats={stats}
            kpis={{ total: 'Pays enregistrés', utilises: 'Utilisés', inutilises: 'Sans étudiant ni campus' }}
            filters={filters}
            urls={urls}
            recherche="Rechercher un pays ou un code"
            icone={<Flag {...ICON} />}
            usage={(p) => ({ total: p.etudiants + p.campus, libelle: 'étudiant(s) ou campus' })}
            conseilSuppression="Modifiez le pays de ces éléments avant de le supprimer."
            colonnes={[
                {
                    key: 'code',
                    header: 'Code',
                    className: 'whitespace-nowrap',
                    cell: (p) => (p.code ? <span className="text-gray-900">{p.code}</span> : <span className="text-gray-400">—</span>),
                },
                {
                    key: 'usage',
                    header: 'Utilisation',
                    className: 'whitespace-nowrap text-sm',
                    cell: (p) =>
                        p.etudiants + p.campus === 0 ? (
                            <span className="text-muted">Inutilisé</span>
                        ) : (
                            <span className="text-gray-900">
                                {nombre(p.etudiants)} étudiant(s) · {nombre(p.campus)} campus
                            </span>
                        ),
                },
            ]}
        />
    );
}
