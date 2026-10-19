import { CalendarDays, Check, FileText, Hotel, Info, ListChecks, Pencil, Star, X } from 'lucide-react';
import type { ReactNode } from 'react';
import { CONDITIONS, NIVEAUX, TYPES_ADMISSION, type Niveau } from '@/components/campagnes/types';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { Bloc, ChoixCampagne, Note, Vide, type CampagneOption } from '@/components/parametrage-hebergement/shared';
import { Badge } from '@/components/ui/badge';
import { buttonVariants } from '@/components/ui/button';
import { PageHeader } from '@/components/ui/page-header';
import { StatCard } from '@/components/ui/stat-card';
import { dateFr, nombre } from '@/lib/text';
import { cn } from '@/lib/utils';

type Regles = {
    periode_debut: string | null;
    periode_fin: string | null;
    date_echeance_demande: string | null;
    date_echeance_contrat: string | null;
    moyenne_min: number | null;
    pourcentage_validate: number | null;
    distance_min: number | null;
    ages: Partial<Record<Niveau, number>>;
    conditions: Record<string, boolean>;
    documents: string[];
    residences: string[];
};

type Props = {
    campagnes: CampagneOption[];
    campagneId: string | null;
    regles: Regles | null;
    urls: { index: string; editCampagne: string | null; scoring: string | null };
};

/**
 * Règles d'éligibilité d'une campagne (remplace la maquette `parametrage-herb/regles-campagne/index.blade.php`, qui affichait des données inventées).
 * Les règles se saisissent dans le formulaire de la campagne : la page les rassemble en lecture seule.
 */
export default function ReglesCampagne({ campagnes, campagneId, regles, urls }: Props) {
    return (
        <AdminLayout title="Règles d’éligibilité">
            <PageHeader
                title="Règles d’éligibilité"
                description="Critères appliqués aux demandes de chaque campagne, vérifiés lors de l’étude des dossiers."
                actions={
                    (urls.editCampagne || urls.scoring) && (
                        <>
                            {urls.scoring && campagneId && (
                                <a href={`${urls.scoring}?campagne_id=${encodeURIComponent(campagneId)}`} className={cn(buttonVariants({ variant: 'secondary', size: 'sm' }), 'h-10')}>
                                    <Star className="size-4" aria-hidden /> Barème de classement
                                </a>
                            )}
                            {urls.editCampagne && (
                                <a href={urls.editCampagne} className={cn(buttonVariants({ size: 'sm' }), 'h-10')}>
                                    <Pencil className="size-4" aria-hidden /> Modifier les règles
                                </a>
                            )}
                        </>
                    )
                }
            />

            {campagnes.length === 0 || !regles ? (
                <Vide icone={<ListChecks />}>Aucune campagne n’est enregistrée : les règles se définissent à la création d’une campagne.</Vide>
            ) : (
                <>
                    <ChoixCampagne campagnes={campagnes} valeur={campagneId} url={urls.index} />

                    <div className="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <StatCard tone="green" label="Moyenne minimale" value={regles.moyenne_min !== null ? `${nombre(regles.moyenne_min, 2)} / 20` : 'Aucune'} />
                        <StatCard tone="blue" label="Crédits validés minimum" value={regles.pourcentage_validate !== null ? `${nombre(regles.pourcentage_validate)} %` : 'Aucun'} />
                        <StatCard tone="amber" label="Distance minimale du domicile" value={regles.distance_min !== null ? `${nombre(regles.distance_min)} km` : 'Aucune'} />
                        <StatCard tone="plum" label="Pièces demandées" value={nombre(regles.documents.length)} />
                    </div>

                    <div className="grid gap-4 lg:grid-cols-2">
                        <Bloc titre="Calendrier" icone={<CalendarDays {...ICON} />}>
                            <Lignes
                                lignes={[
                                    ['Ouverture de la campagne', dateFr(regles.periode_debut)],
                                    ['Clôture de la campagne', dateFr(regles.periode_fin)],
                                    ['Date limite des demandes', dateFr(regles.date_echeance_demande)],
                                    ['Date limite des contrats', dateFr(regles.date_echeance_contrat)],
                                ]}
                            />
                        </Bloc>

                        <Bloc titre="Âge maximum par niveau" icone={<ListChecks {...ICON} />}>
                            {Object.keys(regles.ages).length === 0 ? (
                                <p className="px-4 py-4 text-sm text-muted">Aucune limite d’âge.</p>
                            ) : (
                                <div className="grid grid-cols-2 gap-2 p-4 sm:grid-cols-4">
                                    {(Object.keys(NIVEAUX) as Niveau[])
                                        .filter((n) => regles.ages[n] !== undefined)
                                        .map((n) => (
                                            <div key={n} className="rounded-brand border bg-surface px-3 py-2 text-center">
                                                <p className="text-xs text-muted">{NIVEAUX[n]}</p>
                                                <p className="text-sm font-medium text-gray-900">{regles.ages[n]} ans</p>
                                            </div>
                                        ))}
                                </div>
                            )}
                        </Bloc>

                        <Bloc titre="Public et conditions" icone={<Check {...ICON} />}>
                            <ul className="divide-y">
                                {[...TYPES_ADMISSION, ...CONDITIONS].map((c) => (
                                    <li key={c.name} className="flex items-start justify-between gap-3 px-4 py-2.5 text-sm">
                                        <span>
                                            <span className="block text-gray-900">{c.label}</span>
                                            <span className="block text-xs text-muted">{c.description}</span>
                                        </span>
                                        {regles.conditions[c.name] ? (
                                            <Badge variant="success">
                                                <Check className="size-3" aria-hidden /> Oui
                                            </Badge>
                                        ) : (
                                            <Badge variant="neutral">
                                                <X className="size-3" aria-hidden /> Non
                                            </Badge>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        </Bloc>

                        <div className="space-y-4">
                            <Bloc titre="Documents demandés" icone={<FileText {...ICON} />}>
                                <Liste elements={regles.documents} vide="Aucun document demandé." />
                            </Bloc>
                            <Bloc titre="Résidences ouvertes" icone={<Hotel {...ICON} />}>
                                <Liste elements={regles.residences} vide="Aucune résidence associée." />
                            </Bloc>
                        </div>
                    </div>

                    <div className="mt-4">
                        <Note icone={<Info />}>
                            <p>
                                Ces règles se modifient dans le formulaire de la campagne. Une campagne active a déjà reçu des demandes : un changement de critère ne
                                s’applique qu’aux dossiers étudiés ensuite.
                            </p>
                        </Note>
                    </div>
                </>
            )}
        </AdminLayout>
    );
}

function Lignes({ lignes }: { lignes: [string, ReactNode][] }) {
    return (
        <dl className="divide-y">
            {lignes.map(([label, valeur]) => (
                <div key={label} className="flex items-center justify-between gap-3 px-4 py-2.5 text-sm">
                    <dt className="text-muted">{label}</dt>
                    <dd className="font-medium text-gray-900">{valeur}</dd>
                </div>
            ))}
        </dl>
    );
}

function Liste({ elements, vide }: { elements: string[]; vide: string }) {
    if (elements.length === 0) return <p className="px-4 py-4 text-sm text-muted">{vide}</p>;
    return (
        <ul className="flex flex-wrap gap-1.5 p-4">
            {elements.map((e) => (
                <li key={e}>
                    <Badge variant="success">{e}</Badge>
                </li>
            ))}
        </ul>
    );
}
