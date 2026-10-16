import { Link } from '@inertiajs/react';
import { ArrowLeft, BedDouble, ChartColumn, CircleCheck, Download, Hourglass, Info, ListChecks, TriangleAlert } from 'lucide-react';
import type { ReactNode } from 'react';
import { lienContour, pourcent, type CampagneAllocation, type StatsAllocation } from '@/components/allocations/shared';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { Badge } from '@/components/ui/badge';
import { buttonVariants } from '@/components/ui/button';
import { KpiFilters } from '@/components/ui/kpi-filters';
import { PageHeader } from '@/components/ui/page-header';
import { Progress } from '@/components/ui/progress';
import { nombre } from '@/lib/text';
import { cn } from '@/lib/utils';

type Props = {
    campagne: CampagneAllocation;
    stats: StatsAllocation | null;
    urls: { show: string | null; export: string | null };
};

/** Statistiques d'attribution d'une campagne (remplace `allocation_statistics.blade.php`). */
export default function AllocationStatistics({ campagne, stats, urls }: Props) {
    return (
        <AdminLayout title="Statistiques d’attribution">
            <PageHeader
                title="Statistiques d’attribution"
                description={
                    <>
                        Campagne <strong className="font-medium text-gray-900">{campagne.annee}</strong> · Vue d’ensemble des demandes, des propositions et de l’occupation par type de chambre.
                    </>
                }
                actions={
                    (urls.show || urls.export) && (
                        <>
                            {urls.show && (
                                <Link href={urls.show} className={lienContour}>
                                    <ArrowLeft {...ICON} aria-hidden /> Suivi de la campagne
                                </Link>
                            )}
                            {urls.export && (
                                <a href={urls.export} className={cn(buttonVariants({ size: 'sm' }), 'h-10 px-4')}>
                                    <Download {...ICON} aria-hidden /> Exporter (CSV)
                                </a>
                            )}
                        </>
                    )
                }
            />

            {!stats ? (
                <Carte>
                    <p className="flex flex-col items-center gap-2 py-10 text-sm text-muted">
                        <ChartColumn {...ICON} aria-hidden />
                        Aucune statistique disponible pour cette campagne.
                    </p>
                </Carte>
            ) : (
                <Contenu stats={stats} />
            )}
        </AdminLayout>
    );
}

function Contenu({ stats }: { stats: StatsAllocation }) {
    const taux = stats.taux_allocation;
    const enAttente = stats.assignations_en_attente;
    const confirmees = stats.assignations_confirmees;
    const demandesEnAttente = stats.sans_chambre;

    return (
        <div className="flex flex-col gap-6">
            <KpiFilters
                label="Indicateurs d’attribution"
                items={[
                    { key: 'acceptees', label: 'Demandes attribuées définitivement', value: nombre(stats.demandes_acceptees), tone: 'blue' },
                    { key: 'confirmees', label: 'Attributions confirmées', value: nombre(confirmees), tone: 'green' },
                    { key: 'attente', label: 'Propositions à confirmer', value: nombre(enAttente), tone: 'amber' },
                    { key: 'etude', label: 'Demandes en attente d’étude', value: nombre(demandesEnAttente), tone: 'plum' },
                ]}
            />

            <Carte titre="Taux d’attribution">
                <div className="grid gap-6 lg:grid-cols-2">
                    <div>
                        <p className="flex flex-wrap items-baseline gap-2">
                            <strong className="text-2xl font-medium tabular-nums">{pourcent(taux)}</strong>
                            <span className="text-sm text-muted">des demandes de la campagne sont attribuées définitivement</span>
                        </p>
                        <Progress value={taux} className="mt-2.5" />
                    </div>
                    <dl className="divide-y text-sm">
                        <Ligne terme="Attributions confirmées" valeur={<span className="text-primary">{nombre(confirmees)}</span>} />
                        <Ligne terme="Propositions en attente" valeur={<span className="text-[#A15C00]">{nombre(enAttente)}</span>} />
                        <Ligne terme="Total des attributions actives" valeur={nombre(stats.total_assignations || confirmees + enAttente)} />
                    </dl>
                </div>
            </Carte>

            <Carte titre="Occupation par type de chambre" meta="Attributions de la campagne rapportées au parc de chaque type">
                {stats.par_type.length === 0 ? (
                    <p className="flex flex-col items-center gap-2 py-8 text-sm text-muted">
                        <BedDouble {...ICON} aria-hidden />
                        Aucun type de chambre n’est paramétré.
                    </p>
                ) : (
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        {stats.par_type.map((type) => {
                            const occupation = type.total > 0 ? Math.min(100, Math.round((type.occupees / type.total) * 1000) / 10) : 0;
                            return (
                                <article key={type.nom} className="flex flex-col gap-3 rounded-brand border p-4">
                                    <div className="flex items-center justify-between gap-2">
                                        <h3 className="font-medium text-gray-900">Chambres {type.nom}</h3>
                                        <Badge variant="info">{pourcent(occupation)}</Badge>
                                    </div>
                                    <Progress value={occupation} />
                                    <dl className="divide-y text-sm">
                                        <Ligne terme="Attribuées" valeur={nombre(type.occupees)} />
                                        <Ligne terme="Disponibles" valeur={<span className="text-primary">{nombre(type.disponibles)}</span>} />
                                        <Ligne terme="Total" valeur={nombre(type.total)} />
                                    </dl>
                                </article>
                            );
                        })}
                    </div>
                )}
            </Carte>

            {(demandesEnAttente > 0 || enAttente > 0 || taux < 50 || taux >= 100) && (
                <Carte
                    titre={
                        <span className="flex items-center gap-2">
                            <span className="flex size-8 items-center justify-center rounded-brand bg-[#FEEBC0] text-[#6B3F00]" aria-hidden>
                                <ListChecks size={18} strokeWidth={1.25} />
                            </span>
                            Points d’attention
                        </span>
                    }
                >
                    <ul className="flex flex-col gap-2.5 text-sm">
                        {demandesEnAttente > 0 && (
                            <Point icone={TriangleAlert}>
                                <strong className="font-medium">{nombre(demandesEnAttente)}</strong> demande(s) attendent encore leur étude ; elles ne peuvent pas recevoir de chambre tant qu’elles ne sont
                                pas éligibles.
                            </Point>
                        )}
                        {enAttente > 0 && (
                            <Point icone={Hourglass}>
                                <strong className="font-medium">{nombre(enAttente)}</strong> proposition(s) attendent la réponse de l’étudiant ou la validation définitive.
                            </Point>
                        )}
                        {taux >= 100 ? (
                            <Point icone={CircleCheck} ok>
                                Toutes les demandes de la campagne sont attribuées définitivement.
                            </Point>
                        ) : (
                            taux < 50 && (
                                <Point icone={Info}>Moins de la moitié des demandes sont attribuées définitivement : vérifiez les places disponibles et les dossiers en attente.</Point>
                            )
                        )}
                    </ul>
                </Carte>
            )}
        </div>
    );
}

function Carte({ titre, meta, children }: { titre?: ReactNode; meta?: string; children: ReactNode }) {
    return (
        <section className="overflow-hidden rounded-brand border bg-white">
            {titre && (
                <div className="flex flex-wrap items-center justify-between gap-2 border-b px-4 py-3">
                    <h2 className="text-base font-medium text-gray-900">{titre}</h2>
                    {meta && <span className="text-xs text-muted">{meta}</span>}
                </div>
            )}
            <div className="p-4">{children}</div>
        </section>
    );
}

function Ligne({ terme, valeur }: { terme: string; valeur: ReactNode }) {
    return (
        <div className="flex justify-between gap-4 py-1.5">
            <dt className="text-muted">{terme}</dt>
            <dd className="font-medium tabular-nums">{valeur}</dd>
        </div>
    );
}

function Point({ icone: Icone, ok = false, children }: { icone: typeof Info; ok?: boolean; children: ReactNode }) {
    return (
        <li className="flex items-start gap-2">
            <Icone className={cn('mt-0.5 size-4 shrink-0', ok ? 'text-primary' : 'text-amber-700')} strokeWidth={1.5} aria-hidden />
            <span>{children}</span>
        </li>
    );
}
