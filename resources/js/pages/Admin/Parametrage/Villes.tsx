import { Link, router } from '@inertiajs/react';
import { Map as MapIcon, MapPin } from 'lucide-react';
import { useMemo } from 'react';
import { ICON } from '@/components/layout/app-sidebar';
import { ReferentielPage, type ChampReferentiel, type ElementReferentiel } from '@/components/parametrage-hebergement/referentiel-page';
import { Utilisation } from '@/components/parametrage-hebergement/utilisation';
import { buttonVariants } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types/global';

type Ville = ElementReferentiel & { region_id: string; region: string | null; etudiants: number; autres: number };
type Region = { id: string; name: string };

type Props = {
    elements: Paginated<Ville>;
    regions: Region[];
    stats: { total: number; utilises: number; inutilises: number };
    filters: { q: string; region: string };
    urls: { index: string; store: string | null; regions: string | null };
};

const TOUTES = 'toutes';

/** Villes (remplace `parametrage/villes/index.blade.php`) ; actions `store`, `update`, `delete` inchangées. */
export default function Villes({ elements, regions, stats, filters, urls }: Props) {
    const champs = useMemo<ChampReferentiel[]>(
        () => [
            { cle: 'name', label: 'Nom', max: 150, requis: 'Renseignez le nom de la ville.', placeholder: 'Ex. Bondoukou' },
            {
                cle: 'region_id',
                label: 'Région',
                type: 'liste',
                max: 20,
                requis: 'Choisissez la région de la ville.',
                placeholder: 'Sélectionner la région',
                options: regions.map((r) => ({ value: r.id, label: r.name })),
            },
        ],
        [regions],
    );

    const filtrer = (region: string) =>
        router.get(
            urls.index,
            Object.fromEntries(Object.entries({ q: filters.q, region: region === TOUTES ? '' : region }).filter(([, v]) => v !== '')),
            { preserveState: true, preserveScroll: true, replace: true, only: ['elements', 'stats', 'filters'] },
        );

    return (
        <ReferentielPage
            titre="Villes"
            description="Villes de résidence des étudiants et d’implantation des campus, rattachées à leur région."
            singulier="ville"
            un="une ville"
            feminin
            champs={champs}
            elements={elements}
            stats={stats}
            kpis={{ total: 'Villes enregistrées', utilises: 'Utilisées', inutilises: 'Sans étudiant ni campus' }}
            filters={filters}
            urls={urls}
            recherche="Rechercher une ville ou une région"
            icone={<MapPin {...ICON} />}
            usage={(v) => ({ total: v.etudiants + v.autres, libelle: 'étudiant(s), campus ou compte(s)' })}
            conseilSuppression="Modifiez la ville de ces éléments avant de la supprimer."
            actions={
                urls.regions && (
                    <Link href={urls.regions} className={cn(buttonVariants({ variant: 'outline', size: 'sm' }), 'h-10')}>
                        <MapIcon className="size-4" aria-hidden /> Régions
                    </Link>
                )
            }
            filtres={
                <div className="w-full sm:w-64">
                    <Select value={filters.region || TOUTES} onValueChange={filtrer}>
                        <SelectTrigger className="h-10" aria-label="Région">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={TOUTES}>Toutes les régions</SelectItem>
                            {regions.map((r) => (
                                <SelectItem key={r.id} value={r.id}>
                                    {r.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
            }
            colonnes={[
                {
                    key: 'region',
                    header: 'Région',
                    cell: (v) => (v.region ? <span className="text-gray-900">{v.region}</span> : <span className="text-gray-400">—</span>),
                },
                {
                    key: 'usage',
                    header: 'Utilisation',
                    className: 'whitespace-nowrap text-sm',
                    cell: (v) => <Utilisation etudiants={v.etudiants} autres={v.autres} />,
                },
            ]}
        />
    );
}
