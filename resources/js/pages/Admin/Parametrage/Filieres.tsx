import { Link, router } from '@inertiajs/react';
import { BookOpen, Landmark } from 'lucide-react';
import { useMemo } from 'react';
import { ICON } from '@/components/layout/app-sidebar';
import { ImportReferentiel, RapportImportDialog, type ColonneImport, type RapportImport } from '@/components/parametrage-hebergement/import-referentiel';
import { ReferentielPage, type ChampReferentiel, type ElementReferentiel } from '@/components/parametrage-hebergement/referentiel-page';
import { buttonVariants } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { nombre } from '@/lib/text';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types/global';

type Filiere = ElementReferentiel & { departement_code: string; departement: string | null; etudiants: number };
type Departement = { code: string; name: string; abreviation: string | null };

type Props = {
    elements: Paginated<Filiere>;
    departements: Departement[];
    stats: { total: number; utilises: number; inutilises: number };
    filters: { q: string; departement: string };
    import: RapportImport;
    urls: { index: string; store: string | null; departements: string | null; import: string | null; modele: string | null };
};

const TOUS = 'tous';

const COLONNES_IMPORT: ColonneImport[] = [
    { nom: 'nom', requise: true, aide: 'Nom de la filière (ex. Mathématiques-Informatique).' },
    { nom: 'departement', aide: 'Nom ou abréviation d’un département déjà enregistré.' },
    { nom: 'description', aide: 'Description libre.' },
];

/** Filières (remplace `parametrage/filiere/index.blade.php`) ; actions `store`, `update`, `delete` inchangées, + import en masse. */
export default function Filieres({ elements, departements, stats, filters, import: rapport, urls }: Props) {
    const champs = useMemo<ChampReferentiel[]>(
        () => [
            { cle: 'name', label: 'Nom', max: 150, requis: 'Renseignez le nom de la filière.', placeholder: 'Ex. Mathématiques-Informatique' },
            {
                cle: 'departement_code',
                label: 'Département',
                type: 'liste',
                max: 150,
                placeholder: 'Sélectionner le département',
                hint: 'Facultatif : département de rattachement.',
                options: [
                    { value: '', label: 'Aucun département' },
                    ...departements.map((d) => ({ value: d.code, label: d.name, description: d.abreviation ?? undefined })),
                ],
            },
            { cle: 'description', label: 'Description', type: 'zone', max: 255, placeholder: 'Description de la filière' },
        ],
        [departements],
    );

    const filtrer = (departement: string) =>
        router.get(
            urls.index,
            Object.fromEntries(Object.entries({ q: filters.q, departement: departement === TOUS ? '' : departement }).filter(([, v]) => v !== '')),
            { preserveState: true, preserveScroll: true, replace: true, only: ['elements', 'stats', 'filters'] },
        );

    return (
        <ReferentielPage
            titre="Filières"
            description="Filières d’études proposées aux étudiants, rattachées à leur département."
            singulier="filière"
            un="une filière"
            feminin
            champs={champs}
            elements={elements}
            stats={stats}
            kpis={{ total: 'Filières enregistrées', utilises: 'Suivies par des étudiants', inutilises: 'Sans étudiant' }}
            filters={filters}
            urls={urls}
            recherche="Rechercher une filière ou un département"
            icone={<BookOpen {...ICON} />}
            usage={(f) => ({ total: f.etudiants, libelle: 'étudiant(s)' })}
            conseilSuppression="Modifiez la filière de ces étudiants avant de la supprimer."
            actions={
                <>
                    {urls.departements && (
                        <Link href={urls.departements} className={cn(buttonVariants({ variant: 'outline', size: 'sm' }), 'h-10')}>
                            <Landmark className="size-4" aria-hidden /> Départements
                        </Link>
                    )}
                    {urls.import && (
                        <ImportReferentiel
                            url={urls.import}
                            modele={urls.modele}
                            titre="Importer des filières"
                            colonnes={COLONNES_IMPORT}
                            note="Importez d’abord les départements. Une filière de même nom dans le même département est mise à jour ; les autres lignes sont ajoutées."
                        />
                    )}
                    {/* `key` : un nouvel import rouvre le rapport même si le précédent a été fermé. */}
                    <RapportImportDialog key={JSON.stringify(rapport)} rapport={rapport} />
                </>
            }
            filtres={
                <div className="w-full sm:w-64">
                    <Select value={filters.departement || TOUS} onValueChange={filtrer}>
                        <SelectTrigger className="h-10" aria-label="Département">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={TOUS}>Tous les départements</SelectItem>
                            <SelectItem value="aucun">Sans département</SelectItem>
                            {departements.map((d) => (
                                <SelectItem key={d.code} value={d.code}>
                                    {d.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
            }
            colonnes={[
                {
                    key: 'departement',
                    header: 'Département',
                    cell: (f) => (f.departement ? <span className="text-gray-900">{f.departement}</span> : <span className="text-gray-400">—</span>),
                },
                {
                    key: 'usage',
                    header: 'Étudiants',
                    className: 'whitespace-nowrap text-sm',
                    cell: (f) => (f.etudiants === 0 ? <span className="text-muted">Aucun</span> : <span className="text-gray-900">{nombre(f.etudiants)} étudiant(s)</span>),
                },
            ]}
        />
    );
}
