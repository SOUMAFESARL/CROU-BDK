import { CircleCheck, CircleDashed, CreditCard, LayoutGrid, type LucideIcon, Pencil, Rows3, Settings2, Wallet } from 'lucide-react';
import { useState } from 'react';
import { ReseauDialog } from '@/components/api-paiement/reseau-dialog';
import { ENVIRONNEMENTS, type Reseau } from '@/components/api-paiement/types';
import { DataTable, type DataTableColumn } from '@/components/data-table/data-table';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { fcfa } from '@/components/paiements/shared';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { KpiFilters } from '@/components/ui/kpi-filters';
import { PageHeader } from '@/components/ui/page-header';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { dateFr, nombre } from '@/lib/text';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types/global';

type Encaissement = {
    id: string;
    reference: string;
    loyer_id: string;
    etudiant: string | null;
    student_id: string;
    reseau: string;
    montant: number;
    devise: string;
    status: string;
    status_label: string;
    last_error: string | null;
    created_at: string | null;
};

type Props = {
    providers: Reseau[];
    encaissements: Paginated<Encaissement>;
    kpis: {
        total: number;
        confirmes: number;
        montant_confirme: number;
        a_verifier: number;
        echoues: number;
        en_attente: number;
    };
    filters: { statut: string };
    urls: { index: string };
};

const VARIANTES: Record<string, 'success' | 'warning' | 'danger' | 'neutral' | 'info'> = {
    succeeded: 'success',
    review: 'warning',
    failed: 'danger',
    cancelled: 'neutral',
};

/** API de paiement : réseaux et suivi des encaissements en ligne (remplace `parametrage/payment_providers/index.blade.php`). */
export default function ApiPaiementIndex({ providers, encaissements, kpis, filters, urls }: Props) {
    const [reseau, setReseau] = useState<Reseau | null>(null);
    const [vue, setVue] = useVueReseaux();
    const [etat, setEtat] = useState<FiltreEtat>('tous');
    const visibles = providers.filter((p) => etat === 'tous' || (etat === 'actifs') === p.is_active);
    const actifs = providers.filter((p) => p.is_active).length;
    const payables = providers.filter((p) => p.payable).length;
    const lien = (statut: string) => (statut ? `${urls.index}?statut=${statut}` : urls.index);

    const colonnes: DataTableColumn<Encaissement>[] = [
        {
            key: 'date',
            header: 'Date',
            className: 'whitespace-nowrap',
            cell: (e) => (
                <>
                    {dateFr(e.created_at)}
                    <p className="text-xs text-muted">{e.created_at?.slice(11, 16)}</p>
                </>
            ),
        },
        {
            key: 'reference',
            header: 'Référence / loyer',
            cell: (e) => (
                <div className="min-w-36">
                    <p className="font-medium text-gray-900">{e.reference}</p>
                    <p className="truncate text-xs text-muted">{e.loyer_id}</p>
                </div>
            ),
        },
        {
            key: 'etudiant',
            header: 'Étudiant',
            cell: (e) =>
                e.etudiant ? <span className="font-medium text-gray-900">{e.etudiant}</span> : <span className="text-xs text-muted">{e.student_id}</span>,
        },
        {
            key: 'reseau',
            header: 'Réseau',
            cell: (e) => <Badge variant="info">{e.reseau}</Badge>,
        },
        {
            key: 'montant',
            header: 'Montant',
            className: 'whitespace-nowrap font-medium tabular-nums',
            cell: (e) => (e.devise === 'XOF' ? fcfa(e.montant) : `${nombre(e.montant)} ${e.devise}`),
        },
        {
            key: 'etat',
            header: 'État',
            cell: (e) => (
                <div className="min-w-40 space-y-1">
                    <Badge variant={VARIANTES[e.status] ?? 'neutral'} className="whitespace-normal">
                        {e.status_label}
                    </Badge>
                    {e.last_error && <p className="text-xs text-muted">{e.last_error}</p>}
                </div>
            ),
        },
    ];

    return (
        <AdminLayout title="API de paiement">
            <PageHeader title="API de paiement" />

            <Tabs defaultValue="encaissements">
                <TabsList aria-label="API de paiement">
                    <TabsTrigger value="encaissements">Suivi des encaissements en ligne</TabsTrigger>
                    <TabsTrigger value="reseaux">Réseaux de paiement</TabsTrigger>
                </TabsList>
                <TabsContent value="encaissements">
                    <KpiFilters
                        label="Filtrer les encaissements par état"
                        active={filters.statut || 'tous'}
                        ringActive={Boolean(filters.statut)}
                        className="mb-4"
                        items={[
                            {
                                key: 'confirmes',
                                label: 'Paiements confirmés',
                                value: nombre(kpis.confirmes),
                                tone: 'green',
                                href: lien('confirmes'),
                                extra: fcfa(kpis.montant_confirme),
                            },
                            {
                                key: 'en_attente',
                                label: 'En attente de confirmation',
                                value: nombre(kpis.en_attente),
                                tone: 'blue',
                                href: lien('en_attente'),
                            },
                            {
                                key: 'a_verifier',
                                label: 'À vérifier',
                                value: nombre(kpis.a_verifier),
                                tone: 'amber',
                                href: lien('a_verifier'),
                                extra: kpis.a_verifier > 0 ? 'Rapprochement requis' : undefined,
                                highlight: kpis.a_verifier > 0,
                            },
                            {
                                key: 'echoues',
                                label: 'Non effectués',
                                value: nombre(kpis.echoues),
                                tone: 'plum',
                                href: lien('echoues'),
                            },
                        ]}
                    />
                    <DataTable
                        columns={colonnes}
                        data={encaissements}
                        rowKey={(e) => e.id}
                        toolbar={
                            filters.statut ? (
                                <div className="flex w-full items-center justify-between gap-3 text-sm">
                                    <span className="text-muted">{nombre(encaissements.total)} encaissement(s) pour ce filtre</span>
                                    <a href={urls.index} className="font-medium text-primary hover:underline">
                                        Voir tous les encaissements
                                    </a>
                                </div>
                            ) : undefined
                        }
                        emptyMessage={
                            <span className="flex flex-col items-center gap-2">
                                <Wallet {...ICON} aria-hidden />
                                {filters.statut ? 'Aucun encaissement dans cet état.' : 'Aucun encaissement en ligne.'}
                            </span>
                        }
                    />
                </TabsContent>
                <TabsContent value="reseaux">
                    <div className="mb-3 flex flex-wrap items-center gap-3">
                        <ChoixEtat
                            valeur={etat}
                            onChange={setEtat}
                            compteurs={{ tous: providers.length, actifs, inactifs: providers.length - actifs }}
                        />
                        <span className="text-sm text-muted sm:ml-auto">
                            {nombre(actifs)} activé(s) sur {nombre(providers.length)} · {nombre(payables)} proposé(s) au paiement
                        </span>
                        <ChoixVue valeur={vue} onChange={setVue} />
                    </div>
                    {visibles.length === 0 ? (
                        <p className="rounded-brand border border-dashed bg-white py-16 text-center text-sm text-muted">
                            {etat === 'actifs' ? 'Aucun réseau activé.' : 'Aucun réseau inactif.'}
                        </p>
                    ) : vue === 'cartes' ? (
                        <div className="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                            {visibles.map((p) => (
                                <article key={p.id} className="flex flex-col gap-4 rounded-brand border bg-white p-4">
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="flex min-w-0 items-center gap-3">
                                            <span className="flex size-10 shrink-0 items-center justify-center rounded-brand bg-soft text-primary">
                                                <CreditCard {...ICON} aria-hidden />
                                            </span>
                                            <div className="min-w-0">
                                                <h3 className="truncate text-sm font-medium text-gray-900">{p.name}</h3>
                                                {sousTitre(p) && <p className="text-xs text-muted">{sousTitre(p)}</p>}
                                            </div>
                                        </div>
                                        <EtatReseau reseau={p} />
                                    </div>

                                    <dl className="space-y-2 text-sm">
                                        <div className="flex items-center justify-between gap-3">
                                            <dt className="text-muted">Lien API</dt>
                                            <dd className="max-w-[65%] truncate text-gray-900" title={p.api_url || undefined}>
                                                {p.api_url || <span className="text-muted">Non renseigné</span>}
                                            </dd>
                                        </div>
                                        <div className="flex items-center justify-between gap-3">
                                            <dt className="text-muted">Identifiants</dt>
                                            <dd className="flex items-center gap-1.5 text-gray-900">
                                                <Identifiants reseau={p} />
                                            </dd>
                                        </div>
                                        <div className="flex items-center justify-between gap-3">
                                            <dt className="text-muted">Paiement étudiant</dt>
                                            <dd>
                                                <Proposition reseau={p} />
                                            </dd>
                                        </div>
                                    </dl>

                                    <BoutonConfigurer reseau={p} onClick={() => setReseau(p)} className="mt-auto h-10 w-full" />
                                </article>
                            ))}
                        </div>
                    ) : (
                        <div className="overflow-x-auto rounded-brand border bg-white">
                            <table className="w-full text-sm">
                                <thead className="bg-surface text-left text-xs text-muted">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">Réseau</th>
                                        <th className="px-4 py-3 font-medium">Lien API</th>
                                        <th className="px-4 py-3 font-medium">Identifiants</th>
                                        <th className="px-4 py-3 font-medium">Paiement étudiant</th>
                                        <th className="px-4 py-3 font-medium">État</th>
                                        <th className="px-4 py-3 font-medium">
                                            <span className="sr-only">Actions</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {visibles.map((p) => (
                                        <tr key={p.id}>
                                            <td className="px-4 py-3">
                                                <div className="flex min-w-44 items-center gap-3">
                                                    <span className="flex size-9 shrink-0 items-center justify-center rounded-brand bg-soft text-primary">
                                                        <CreditCard {...ICON} aria-hidden />
                                                    </span>
                                                    <div className="min-w-0">
                                                        <p className="truncate font-medium text-gray-900">{p.name}</p>
                                                        {sousTitre(p) && <p className="text-xs text-muted">{sousTitre(p)}</p>}
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="max-w-56 truncate px-4 py-3 text-gray-900" title={p.api_url || undefined}>
                                                {p.api_url || <span className="text-muted">Non renseigné</span>}
                                            </td>
                                            <td className="px-4 py-3">
                                                <span className="flex items-center gap-1.5 whitespace-nowrap text-gray-900">
                                                    <Identifiants reseau={p} />
                                                </span>
                                            </td>
                                            <td className="px-4 py-3">
                                                <Proposition reseau={p} />
                                            </td>
                                            <td className="px-4 py-3">
                                                <EtatReseau reseau={p} />
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                <BoutonConfigurer reseau={p} onClick={() => setReseau(p)} />
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </TabsContent>
            </Tabs>

            <ReseauDialog reseau={reseau} onClose={() => setReseau(null)} />
        </AdminLayout>
    );
}

type Vue = 'cartes' | 'liste';
const CLE_VUE = 'api-paiement.vue.reseaux';

/** Mode d'affichage des réseaux, mémorisé par navigateur (sans effet si le stockage est indisponible). */
function useVueReseaux(): [Vue, (vue: Vue) => void] {
    const [vue, setVue] = useState<Vue>(() => {
        try {
            return window.localStorage.getItem(CLE_VUE) === 'liste' ? 'liste' : 'cartes';
        } catch {
            return 'cartes';
        }
    });

    return [
        vue,
        (suivante) => {
            setVue(suivante);
            try {
                window.localStorage.setItem(CLE_VUE, suivante);
            } catch {
                // Stockage indisponible (navigation privée) : la vue n'est simplement pas mémorisée.
            }
        },
    ];
}

const OPTIONS_VUE: { value: Vue; label: string; icon: LucideIcon }[] = [
    { value: 'cartes', label: 'Cartes', icon: LayoutGrid },
    { value: 'liste', label: 'Liste', icon: Rows3 },
];

function ChoixVue({ valeur, onChange }: { valeur: Vue; onChange: (vue: Vue) => void }) {
    return (
        <div role="radiogroup" aria-label="Affichage des réseaux" className="inline-flex h-10 shrink-0 rounded-brand border bg-white p-0.5">
            {OPTIONS_VUE.map((option) => {
                const actif = valeur === option.value;
                return (
                    <button
                        key={option.value}
                        type="button"
                        role="radio"
                        aria-checked={actif}
                        onClick={() => onChange(option.value)}
                        className={cn(
                            'inline-flex cursor-pointer items-center gap-1.5 rounded-[6px] px-3 text-sm font-medium transition-colors focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none',
                            actif ? 'bg-primary text-white' : 'text-muted hover:bg-soft hover:text-primary',
                        )}
                    >
                        <option.icon size={16} strokeWidth={1.5} aria-hidden />
                        {option.label}
                    </button>
                );
            })}
        </div>
    );
}

type FiltreEtat = 'tous' | 'actifs' | 'inactifs';

const OPTIONS_ETAT: { value: FiltreEtat; label: string }[] = [
    { value: 'tous', label: 'Tous' },
    { value: 'actifs', label: 'Activés' },
    { value: 'inactifs', label: 'Inactifs' },
];

function ChoixEtat({ valeur, onChange, compteurs }: { valeur: FiltreEtat; onChange: (etat: FiltreEtat) => void; compteurs: Record<FiltreEtat, number> }) {
    return (
        <div role="radiogroup" aria-label="Filtrer les réseaux par état" className="inline-flex h-10 shrink-0 rounded-brand border bg-white p-0.5">
            {OPTIONS_ETAT.map((option) => {
                const actif = valeur === option.value;
                return (
                    <button
                        key={option.value}
                        type="button"
                        role="radio"
                        aria-checked={actif}
                        onClick={() => onChange(option.value)}
                        className={cn(
                            'inline-flex cursor-pointer items-center gap-1.5 rounded-[6px] px-3 text-sm font-medium transition-colors focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none',
                            actif ? 'bg-primary text-white' : 'text-muted hover:bg-soft hover:text-primary',
                        )}
                    >
                        {option.label}
                        <span className={cn('rounded-full px-1.5 text-xs tabular-nums', actif ? 'bg-white/20' : 'bg-surface')}>{nombre(compteurs[option.value])}</span>
                    </button>
                );
            })}
        </div>
    );
}

const manquants = (p: Reseau) => p.credentials.filter((c) => c.required && !c.saved).length;

/** Environnement affiché sous le nom (MTN uniquement). */
function sousTitre(p: Reseau): string | null {
    if (p.code !== 'mtn') return null;
    return ENVIRONNEMENTS.find((e) => e.value === p.environment)?.label ?? null;
}

function EtatReseau({ reseau }: { reseau: Reseau }) {
    return reseau.is_active ? <Badge variant="success">Activé</Badge> : <Badge variant="neutral">Inactif · masqué</Badge>;
}

function Proposition({ reseau }: { reseau: Reseau }) {
    return reseau.payable ? <Badge variant="success">Proposé</Badge> : <Badge variant="neutral">Non proposé</Badge>;
}

function Identifiants({ reseau }: { reseau: Reseau }) {
    const n = manquants(reseau);
    return n === 0 ? (
        <>
            <CircleCheck className="size-4 text-primary" aria-hidden /> Complets
        </>
    ) : (
        <>
            <CircleDashed className="size-4 text-amber-600" aria-hidden /> {n} à renseigner
        </>
    );
}

/** Tant qu'il manque des identifiants obligatoires : « Configurer » (orange) ; une fois configuré : « Modifier » (contour vert). */
function BoutonConfigurer({ reseau, onClick, className }: { reseau: Reseau; onClick: () => void; className?: string }) {
    if (!reseau.update_url) return null;

    return manquants(reseau) === 0 ? (
        <Button variant="secondary" size="sm" className={className} onClick={onClick}>
            <Pencil className="size-4" aria-hidden /> Modifier
        </Button>
    ) : (
        <Button variant="soft-accent" size="sm" className={className} onClick={onClick}>
            <Settings2 className="size-4" aria-hidden /> Configurer
        </Button>
    );
}
