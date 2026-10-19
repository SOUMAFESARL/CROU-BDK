import { Building, Hotel, Info, Pencil } from 'lucide-react';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { Bloc, ChoixCampagne, Note, taux, Vide, type CampagneOption, type Capacite } from '@/components/parametrage-hebergement/shared';
import { Badge } from '@/components/ui/badge';
import { buttonVariants } from '@/components/ui/button';
import { PageHeader } from '@/components/ui/page-header';
import { Progress } from '@/components/ui/progress';
import { StatCard } from '@/components/ui/stat-card';
import { nombre } from '@/lib/text';
import { cn } from '@/lib/utils';

type BatimentLigne = Capacite & { id: string; name: string; actif: boolean; type_occupation: string | null };
type Residence = Capacite & { id: string; name: string; type: string | null; actif: boolean; batiments: BatimentLigne[] };

type Props = {
    campagnes: CampagneOption[];
    campagneId: string | null;
    batiments: Residence[] | null;
    urls: { index: string; editCampagne: string | null; parc: string | null };
};

/**
 * Résidences et bâtiments ouverts à une campagne (remplace la maquette `parametrage-herb/batiments-campagne/index.blade.php`).
 * Les résidences se choisissent dans le formulaire de la campagne ; les chiffres viennent du parc immobilier.
 */
export default function BatimentsCampagne({ campagnes, campagneId, batiments, urls }: Props) {
    const residences = batiments ?? [];
    const somme = (cle: keyof Capacite) => residences.reduce((total, r) => total + r[cle], 0);

    return (
        <AdminLayout title="Bâtiments par campagne">
            <PageHeader
                title="Bâtiments par campagne"
                description="Résidences ouvertes aux demandes de la campagne, avec leurs bâtiments et leurs places."
                actions={
                    (urls.parc || urls.editCampagne) && (
                        <>
                            {urls.parc && (
                                <a href={urls.parc} className={cn(buttonVariants({ variant: 'secondary', size: 'sm' }), 'h-10')}>
                                    <Building className="size-4" aria-hidden /> Parc immobilier
                                </a>
                            )}
                            {urls.editCampagne && (
                                <a href={urls.editCampagne} className={cn(buttonVariants({ size: 'sm' }), 'h-10')}>
                                    <Pencil className="size-4" aria-hidden /> Modifier les résidences
                                </a>
                            )}
                        </>
                    )
                }
            />

            {campagnes.length === 0 || batiments === null ? (
                <Vide icone={<Building />}>Aucune campagne n’est enregistrée : les résidences se choisissent à la création d’une campagne.</Vide>
            ) : (
                <>
                    <ChoixCampagne campagnes={campagnes} valeur={campagneId} url={urls.index} />

                    <div className="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <StatCard tone="green" label="Résidences ouvertes" value={nombre(residences.length)} />
                        <StatCard tone="blue" label="Bâtiments" value={nombre(residences.reduce((t, r) => t + r.batiments.length, 0))} extra={`${nombre(somme('chambres'))} chambres`} />
                        <StatCard tone="amber" label="Places" value={nombre(somme('places'))} extra={`${nombre(somme('pmr'))} chambre(s) PMR`} />
                        <StatCard tone="plum" label="Places libres" value={nombre(somme('libres'))} highlight={somme('places') > 0 && somme('libres') === 0} />
                    </div>

                    {residences.length === 0 ? (
                        <Vide icone={<Hotel />}>Aucune résidence n’est associée à cette campagne.</Vide>
                    ) : (
                        <div className="space-y-4">
                            {residences.map((r) => (
                                <Bloc
                                    key={r.id}
                                    icone={<Hotel {...ICON} />}
                                    titre={
                                        <span className="flex flex-wrap items-center gap-2">
                                            {r.name}
                                            {r.type && <Badge variant="info">{r.type}</Badge>}
                                            {!r.actif && <Badge variant="danger">Désactivée</Badge>}
                                        </span>
                                    }
                                    actions={
                                        <span className="text-xs font-normal text-muted">
                                            {nombre(r.places)} places · {nombre(r.libres)} libre(s)
                                        </span>
                                    }
                                >
                                    {r.batiments.length === 0 ? (
                                        <p className="px-4 py-4 text-sm text-muted">Aucun bâtiment dans cette résidence.</p>
                                    ) : (
                                        <div className="overflow-x-auto">
                                            <table className="w-full text-sm">
                                                <thead className="bg-surface text-left text-xs text-muted">
                                                    <tr>
                                                        <th className="px-4 py-2.5 font-medium">Bâtiment</th>
                                                        <th className="px-4 py-2.5 font-medium">Occupation</th>
                                                        <th className="px-4 py-2.5 font-medium">Chambres</th>
                                                        <th className="px-4 py-2.5 font-medium">Places</th>
                                                        <th className="px-4 py-2.5 font-medium">Libres</th>
                                                        <th className="px-4 py-2.5 font-medium">Taux d’occupation</th>
                                                        <th className="px-4 py-2.5 font-medium">Statut</th>
                                                    </tr>
                                                </thead>
                                                <tbody className="divide-y">
                                                    {r.batiments.map((b) => (
                                                        <tr key={b.id}>
                                                            <td className="px-4 py-3 font-medium whitespace-nowrap text-gray-900">{b.name}</td>
                                                            <td className="px-4 py-3 whitespace-nowrap text-muted">{b.type_occupation ?? '—'}</td>
                                                            <td className="px-4 py-3 tabular-nums">
                                                                {nombre(b.chambres)}
                                                                {b.pmr > 0 && <span className="text-xs text-muted"> · {nombre(b.pmr)} PMR</span>}
                                                            </td>
                                                            <td className="px-4 py-3 tabular-nums">{nombre(b.places)}</td>
                                                            <td className="px-4 py-3 font-medium tabular-nums">{nombre(b.libres)}</td>
                                                            <td className="px-4 py-3">
                                                                <div className="flex w-36 items-center gap-2">
                                                                    <Progress value={taux(b)} label={`${taux(b)} % des places occupées`} />
                                                                    <span className="w-10 text-right text-xs tabular-nums text-muted">{taux(b)} %</span>
                                                                </div>
                                                            </td>
                                                            <td className="px-4 py-3">{b.actif ? <Badge variant="success">Actif</Badge> : <Badge variant="danger">Désactivé</Badge>}</td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    )}
                                </Bloc>
                            ))}
                        </div>
                    )}

                    <div className="mt-4">
                        <Note icone={<Info />}>
                            <p>
                                Seules les chambres actives et disponibles comptent dans les places libres. Une résidence ou un bâtiment désactivé n’est pas proposé à
                                l’attribution, même s’il est ouvert à la campagne.
                            </p>
                        </Note>
                    </div>
                </>
            )}
        </AdminLayout>
    );
}
