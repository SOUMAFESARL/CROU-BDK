import { BedDouble, Building, ChartPie, Info, Pencil, Users } from 'lucide-react';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { Bloc, ChoixCampagne, Note, taux, Vide, type CampagneOption, type Capacite } from '@/components/parametrage-hebergement/shared';
import { buttonVariants } from '@/components/ui/button';
import { PageHeader } from '@/components/ui/page-header';
import { Progress } from '@/components/ui/progress';
import { StatCard } from '@/components/ui/stat-card';
import { nombre } from '@/lib/text';
import { cn } from '@/lib/utils';

type Quotas = {
    capacite: Capacite;
    types: (Capacite & { type: string })[];
    demandes: { total: number; boursiers: number; genres: { genre: string; total: number }[] };
};

type Props = {
    campagnes: CampagneOption[];
    campagneId: string | null;
    quotas: Quotas | null;
    urls: { index: string; editCampagne: string | null; batiments: string | null };
};

const part = (n: number, total: number) => (total > 0 ? Math.round((n / total) * 100) : 0);

/**
 * Capacités et demandes d'une campagne (remplace la maquette `parametrage-herb/quotas-campagne/index.blade.php`, aux chiffres inventés).
 * Aucun quota n'est encore enregistré par l'application : la page montre l'offre réelle face à la demande.
 */
export default function QuotasCampagne({ campagnes, campagneId, quotas, urls }: Props) {
    return (
        <AdminLayout title="Capacités et quotas">
            <PageHeader
                title="Capacités et quotas"
                description="Places offertes par les résidences de la campagne, par type de chambre, et répartition des demandes reçues."
                actions={
                    (urls.batiments || urls.editCampagne) && (
                        <>
                            {urls.batiments && campagneId && (
                                <a href={`${urls.batiments}?campagne_id=${encodeURIComponent(campagneId)}`} className={cn(buttonVariants({ variant: 'secondary', size: 'sm' }), 'h-10')}>
                                    <Building className="size-4" aria-hidden /> Détail par bâtiment
                                </a>
                            )}
                            {urls.editCampagne && (
                                <a href={urls.editCampagne} className={cn(buttonVariants({ size: 'sm' }), 'h-10')}>
                                    <Pencil className="size-4" aria-hidden /> Modifier la campagne
                                </a>
                            )}
                        </>
                    )
                }
            />

            {campagnes.length === 0 || !quotas ? (
                <Vide icone={<ChartPie />}>Aucune campagne n’est enregistrée.</Vide>
            ) : (
                <>
                    <ChoixCampagne campagnes={campagnes} valeur={campagneId} url={urls.index} />

                    <div className="mb-4">
                        <Note icone={<Info />} ton="attention">
                            <p>
                                Les quotas (boursiers, PMR, filles / garçons…) ne sont pas encore enregistrés ni appliqués par l’attribution. Le classement suit le barème
                                de scoring de la campagne, dans la limite des places libres ci-dessous.
                            </p>
                        </Note>
                    </div>

                    <div className="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <StatCard tone="green" label="Places offertes" value={nombre(quotas.capacite.places)} extra={`${nombre(quotas.capacite.chambres)} chambres`} />
                        <StatCard tone="blue" label="Places libres" value={nombre(quotas.capacite.libres)} extra={`${taux(quotas.capacite)} % occupées`} />
                        <StatCard
                            tone="amber"
                            label="Demandes en cours"
                            value={nombre(quotas.demandes.total)}
                            extra={quotas.capacite.libres > 0 ? `${nombre(quotas.demandes.total / quotas.capacite.libres, 1)} par place libre` : undefined}
                            highlight={quotas.demandes.total > quotas.capacite.libres}
                        />
                        <StatCard tone="plum" label="Chambres PMR" value={nombre(quotas.capacite.pmr)} />
                    </div>

                    <div className="grid gap-4 lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
                        <Bloc titre="Places par type de chambre" icone={<BedDouble {...ICON} />}>
                            {quotas.types.length === 0 ? (
                                <p className="px-4 py-4 text-sm text-muted">Aucune chambre dans les résidences de cette campagne.</p>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm">
                                        <thead className="bg-surface text-left text-xs text-muted">
                                            <tr>
                                                <th className="px-4 py-2.5 font-medium">Type</th>
                                                <th className="px-4 py-2.5 font-medium">Chambres</th>
                                                <th className="px-4 py-2.5 font-medium">Places</th>
                                                <th className="px-4 py-2.5 font-medium">Libres</th>
                                                <th className="px-4 py-2.5 font-medium">Part de l’offre</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y">
                                            {quotas.types.map((t) => (
                                                <tr key={t.type}>
                                                    <td className="px-4 py-3 font-medium whitespace-nowrap text-gray-900">{t.type}</td>
                                                    <td className="px-4 py-3 tabular-nums">
                                                        {nombre(t.chambres)}
                                                        {t.pmr > 0 && <span className="text-xs text-muted"> · {nombre(t.pmr)} PMR</span>}
                                                    </td>
                                                    <td className="px-4 py-3 tabular-nums">{nombre(t.places)}</td>
                                                    <td className="px-4 py-3 font-medium tabular-nums">{nombre(t.libres)}</td>
                                                    <td className="px-4 py-3">
                                                        <Repartition valeur={part(t.places, quotas.capacite.places)} />
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </Bloc>

                        <Bloc titre="Demandes reçues" icone={<Users {...ICON} />}>
                            {quotas.demandes.total === 0 ? (
                                <p className="px-4 py-4 text-sm text-muted">Aucune demande en cours pour cette campagne.</p>
                            ) : (
                                <ul className="divide-y">
                                    <Ligne label="Boursiers" n={quotas.demandes.boursiers} total={quotas.demandes.total} />
                                    <Ligne label="Non boursiers" n={quotas.demandes.total - quotas.demandes.boursiers} total={quotas.demandes.total} />
                                    {quotas.demandes.genres.map((g) => (
                                        <Ligne key={g.genre} label={g.genre} n={g.total} total={quotas.demandes.total} />
                                    ))}
                                </ul>
                            )}
                            <p className="border-t px-4 py-2.5 text-xs text-muted">Dossiers soumis à attribués ; brouillons, annulés et expirés exclus.</p>
                        </Bloc>
                    </div>
                </>
            )}
        </AdminLayout>
    );
}

function Repartition({ valeur }: { valeur: number }) {
    return (
        <div className="flex w-36 items-center gap-2">
            <Progress value={valeur} label={`${valeur} %`} />
            <span className="w-10 text-right text-xs tabular-nums text-muted">{valeur} %</span>
        </div>
    );
}

function Ligne({ label, n, total }: { label: string; n: number; total: number }) {
    return (
        <li className="flex items-center justify-between gap-3 px-4 py-2.5 text-sm">
            <span className="text-gray-900">{label}</span>
            <span className="flex items-center gap-3">
                <span className="font-medium tabular-nums">{nombre(n)}</span>
                <Repartition valeur={part(n, total)} />
            </span>
        </li>
    );
}
