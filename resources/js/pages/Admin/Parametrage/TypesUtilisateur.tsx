import { UserCog } from 'lucide-react';
import { ICON } from '@/components/layout/app-sidebar';
import { ReferentielPage, type ChampReferentiel, type ElementReferentiel } from '@/components/parametrage-hebergement/referentiel-page';
import { nombre } from '@/lib/text';
import type { Paginated } from '@/types/global';

type TypeUtilisateur = ElementReferentiel & { utilisateurs: number };

type Props = {
    elements: Paginated<TypeUtilisateur>;
    stats: { total: number; utilises: number; inutilises: number };
    filters: { q: string };
    urls: { index: string; store: string | null };
};

const CHAMPS: ChampReferentiel[] = [{ cle: 'name', label: 'Nom', max: 100, requis: 'Renseignez le nom du type.', placeholder: 'Ex. Administratif' }];

/** Types d'utilisateur (remplace `parametrage/type-utilisateur/index.blade.php`) ; actions `store`, `update`, `delete` inchangées. */
export default function TypesUtilisateur({ elements, stats, filters, urls }: Props) {
    return (
        <ReferentielPage
            titre="Types d’utilisateur"
            description="Catégories de personnel (administratif, technique, enseignant…) attribuées aux comptes utilisateurs."
            singulier="type d’utilisateur"
            un="un type d’utilisateur"
            champs={CHAMPS}
            elements={elements}
            stats={stats}
            kpis={{ total: 'Types enregistrés', utilises: 'Attribués', inutilises: 'Sans utilisateur' }}
            filters={filters}
            urls={urls}
            recherche="Rechercher un type"
            icone={<UserCog {...ICON} />}
            usage={(t) => ({ total: t.utilisateurs, libelle: 'utilisateur(s)' })}
            conseilSuppression="Modifiez le type de ces comptes avant de le supprimer."
            colonnes={[
                {
                    key: 'usage',
                    header: 'Utilisateurs',
                    className: 'whitespace-nowrap text-sm',
                    cell: (t) => (t.utilisateurs === 0 ? <span className="text-muted">Aucun</span> : <span className="text-gray-900">{nombre(t.utilisateurs)} compte(s)</span>),
                },
            ]}
        />
    );
}
