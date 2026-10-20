import { Landmark } from 'lucide-react';
import { ICON } from '@/components/layout/app-sidebar';
import { ReferentielPage, type ChampReferentiel, type ElementReferentiel } from '@/components/parametrage-hebergement/referentiel-page';
import { nombre } from '@/lib/text';
import type { Paginated } from '@/types/global';

type Departement = ElementReferentiel & { abreviation: string | null; filieres: number; etudiants: number };

type Props = {
    elements: Paginated<Departement>;
    stats: { total: number; utilises: number; inutilises: number };
    filters: { q: string };
    urls: { index: string; store: string | null };
};

const CHAMPS: ChampReferentiel[] = [
    { cle: 'name', label: 'Nom', max: 255, requis: 'Renseignez le nom du département.', placeholder: 'Ex. Sciences et technologies' },
    { cle: 'abreviation', label: 'Abréviation', max: 100, placeholder: 'Ex. UFR ST', hint: 'Facultative : sigle affiché dans les listes.' },
    { cle: 'description', label: 'Description', type: 'zone', max: 2000, placeholder: 'Description du département' },
];

/** Départements (remplace `parametrage/departement/index.blade.php`) ; actions `store`, `update`, `delete` inchangées. */
export default function Departements({ elements, stats, filters, urls }: Props) {
    return (
        <ReferentielPage
            titre="Départements"
            description="Départements et UFR de rattachement des filières et des étudiants."
            singulier="département"
            un="un département"
            champs={CHAMPS}
            elements={elements}
            stats={stats}
            kpis={{ total: 'Départements enregistrés', utilises: 'Utilisés', inutilises: 'Sans filière ni étudiant' }}
            filters={filters}
            urls={urls}
            recherche="Rechercher un nom, un sigle ou une description"
            icone={<Landmark {...ICON} />}
            usage={(d) => ({ total: d.filieres + d.etudiants, libelle: 'filière(s) ou étudiant(s)' })}
            conseilSuppression="Rattachez ces éléments à un autre département avant de le supprimer."
            colonnes={[
                {
                    key: 'abreviation',
                    header: 'Abréviation',
                    className: 'whitespace-nowrap',
                    cell: (d) => (d.abreviation ? <span className="text-gray-900">{d.abreviation}</span> : <span className="text-gray-400">—</span>),
                },
                {
                    key: 'usage',
                    header: 'Utilisation',
                    className: 'whitespace-nowrap text-sm',
                    cell: (d) =>
                        d.filieres + d.etudiants === 0 ? (
                            <span className="text-muted">Inutilisé</span>
                        ) : (
                            <span className="text-gray-900">
                                {nombre(d.filieres)} filière(s) · {nombre(d.etudiants)} étudiant(s)
                            </span>
                        ),
                },
            ]}
        />
    );
}
