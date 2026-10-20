import { Link } from '@inertiajs/react';
import { Map as MapIcon, MapPin } from 'lucide-react';
import { ICON } from '@/components/layout/app-sidebar';
import { ReferentielPage, type ChampReferentiel, type ElementReferentiel } from '@/components/parametrage-hebergement/referentiel-page';
import { Utilisation } from '@/components/parametrage-hebergement/utilisation';
import { buttonVariants } from '@/components/ui/button';
import { nombre } from '@/lib/text';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types/global';

type Region = ElementReferentiel & { villes: number; etudiants: number; autres: number };

type Props = {
    elements: Paginated<Region>;
    stats: { total: number; utilises: number; inutilises: number };
    filters: { q: string };
    urls: { index: string; store: string | null; villes: string | null };
};

const CHAMPS: ChampReferentiel[] = [{ cle: 'name', label: 'Nom', max: 150, requis: 'Renseignez le nom de la région.', placeholder: 'Ex. Région du Gontougo' }];

/** Régions (remplace `parametrage/regions/index.blade.php`) ; actions `store`, `update`, `delete` inchangées. */
export default function Regions({ elements, stats, filters, urls }: Props) {
    return (
        <ReferentielPage
            titre="Régions"
            description="Découpage régional utilisé pour les villes, les étudiants et les campus."
            singulier="région"
            un="une région"
            feminin
            champs={CHAMPS}
            elements={elements}
            stats={stats}
            kpis={{ total: 'Régions enregistrées', utilises: 'Utilisées', inutilises: 'Sans ville ni étudiant' }}
            filters={filters}
            urls={urls}
            recherche="Rechercher une région"
            icone={<MapIcon {...ICON} />}
            usage={(r) => ({ total: r.villes + r.etudiants + r.autres, libelle: 'ville(s), étudiant(s), campus ou compte(s)' })}
            conseilSuppression="Rattachez ces éléments à une autre région avant de la supprimer."
            actions={
                urls.villes && (
                    <Link href={urls.villes} className={cn(buttonVariants({ variant: 'outline', size: 'sm' }), 'h-10')}>
                        <MapPin className="size-4" aria-hidden /> Villes
                    </Link>
                )
            }
            colonnes={[
                {
                    key: 'villes',
                    header: 'Villes',
                    className: 'whitespace-nowrap text-sm',
                    cell: (r) => (r.villes === 0 ? <span className="text-muted">Aucune</span> : <span className="text-gray-900">{nombre(r.villes)} ville(s)</span>),
                },
                {
                    key: 'usage',
                    header: 'Utilisation',
                    className: 'whitespace-nowrap text-sm',
                    cell: (r) => <Utilisation etudiants={r.etudiants} autres={r.autres} />,
                },
            ]}
        />
    );
}
