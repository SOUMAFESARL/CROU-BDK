import { Sofa, Toolbox } from 'lucide-react';
import { ICON } from '@/components/layout/app-sidebar';
import { ReferentielPage, type ElementReferentiel } from '@/components/parametrage-hebergement/referentiel-page';
import { buttonVariants } from '@/components/ui/button';
import { nombre } from '@/lib/text';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types/global';

type TypeEquipement = ElementReferentiel & { equipements: number };

type Props = {
    types: Paginated<TypeEquipement>;
    stats: { total: number; utilises: number; inutilises: number };
    filters: { q: string };
    urls: { index: string; store: string | null; equipements: string | null };
};

/** Types d'équipements (remplace `parametrage-herb/type-equipement/index.blade.php`) ; actions `store`, `update`, `destroy` inchangées. */
export default function TypesEquipement({ types, stats, filters, urls }: Props) {
    return (
        <ReferentielPage
            titre="Types d’équipements"
            description="Familles de matériel utilisées pour classer le catalogue des équipements."
            singulier="type d’équipement"
            un="un type d’équipement"
            nameMax={255}
            elements={types}
            stats={stats}
            filters={filters}
            urls={urls}
            icone={<Sofa {...ICON} />}
            usage={(t) => ({ total: t.equipements, libelle: 'équipement(s) du catalogue' })}
            actions={
                urls.equipements && (
                    <a href={urls.equipements} className={cn(buttonVariants({ variant: 'secondary', size: 'sm' }), 'h-10')}>
                        <Toolbox className="size-4" aria-hidden /> Catalogue des équipements
                    </a>
                )
            }
            colonnes={[
                {
                    key: 'usage',
                    header: 'Équipements',
                    className: 'whitespace-nowrap text-sm',
                    cell: (t) => (t.equipements === 0 ? <span className="text-muted">Aucun</span> : <span className="text-gray-900">{nombre(t.equipements)}</span>),
                },
            ]}
        />
    );
}
