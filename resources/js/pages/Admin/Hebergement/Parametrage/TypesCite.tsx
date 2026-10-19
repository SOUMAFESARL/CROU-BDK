import { Hotel, Info } from 'lucide-react';
import { ICON } from '@/components/layout/app-sidebar';
import { Note } from '@/components/parametrage-hebergement/shared';
import { ReferentielPage, type ElementReferentiel } from '@/components/parametrage-hebergement/referentiel-page';
import { nombre } from '@/lib/text';
import type { Paginated } from '@/types/global';

type TypeCite = ElementReferentiel & { code: string | null; cites: number; batiments: number };

type Props = {
    types: Paginated<TypeCite>;
    stats: { total: number; utilises: number; inutilises: number };
    filters: { q: string };
    urls: { index: string; parametrage: string; store: string | null };
};

/** Types de cité (remplace `parametrage-herb/type-cite/index.blade.php`) ; actions `store`, `update`, `destroy` inchangées. */
export default function TypesCite({ types, stats, filters, urls }: Props) {
    return (
        <ReferentielPage
            titre="Types de cité"
            description="Catégories proposées à la création des cités et comme type d’occupation des bâtiments."
            singulier="type de cité"
            un="un type de cité"
            nameMax={250}
            elements={types}
            stats={stats}
            filters={filters}
            urls={urls}
            icone={<Hotel {...ICON} />}
            usage={(t) => ({ total: t.cites + t.batiments, libelle: 'cité(s) ou bâtiment(s)' })}
            note={
                <Note icone={<Info />}>
                    <p>
                        Le nom compte pour l’attribution des chambres : un type dont le nom contient « Homme », « Garçon », « Femme » ou « Fille » réserve la cité
                        ou le bâtiment à ce sexe. Vérifiez les attributions avant de renommer un type utilisé.
                    </p>
                </Note>
            }
            colonnes={[
                {
                    key: 'usage',
                    header: 'Utilisation',
                    className: 'whitespace-nowrap text-sm',
                    cell: (t) =>
                        t.cites + t.batiments === 0 ? (
                            <span className="text-muted">Inutilisé</span>
                        ) : (
                            <span className="text-gray-900">
                                {nombre(t.cites)} cité(s) · {nombre(t.batiments)} bâtiment(s)
                            </span>
                        ),
                },
            ]}
        />
    );
}
