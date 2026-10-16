import { Link, router } from '@inertiajs/react';
import { ArrowLeft, CalendarRange, Check, Printer, ReceiptText, TriangleAlert, UserRound } from 'lucide-react';
import { useState, type ReactNode } from 'react';
import type { DataTableColumn } from '@/components/data-table/data-table';
import { SimpleTable } from '@/components/data-table/simple-table';
import { ConfirmDialog } from '@/components/feedback/confirm-dialog';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { StatutPaiementBadge } from '@/components/paiements/shared';
import { Badge } from '@/components/ui/badge';
import { Button, buttonVariants } from '@/components/ui/button';
import { PageHeader } from '@/components/ui/page-header';
import { dateFr } from '@/lib/text';
import { cn } from '@/lib/utils';

type Repartition = { id: string; type: string; periode: string | null; montant: string; description: string | null };

type Props = {
    paiement: {
        id: string;
        numero: string | null;
        montant: string;
        methode: string;
        type: string;
        statut: string;
        statut_label: string;
        date_paiement: string | null;
        date_echeance: string | null;
        reference: string | null;
        cree_par: string | null;
        notes: string | null;
        mois: string[];
        resident: { name: string | null; matricule: string | null; telephone: string | null; email: string | null; residence: string | null; chambre: string | null };
        repartitions: Repartition[];
        total_reparti: string;
        ecart: string | null;
    };
    urls: { index: string | null; valider: string | null };
};

const TYPES_REPARTITION: Record<string, { label: string; variant: 'danger' | 'success' | 'info' }> = {
    arriere: { label: 'Arriéré', variant: 'danger' },
    mois_cours: { label: 'Mois en cours', variant: 'success' },
    avance: { label: 'Avance', variant: 'info' },
};

/** Détail d'un paiement (remplace `vie_residentielle/paiement/show.blade.php`). */
export default function PaiementShow({ paiement, urls }: Props) {
    const [confirmation, setConfirmation] = useState(false);
    const [traitement, setTraitement] = useState(false);
    const date = paiement.date_paiement ? `${dateFr(paiement.date_paiement)} à ${paiement.date_paiement.slice(11, 16)}` : null;

    const valider = () => {
        if (!urls.valider) return;
        router.post(urls.valider, {}, {
            preserveScroll: true,
            onStart: () => setTraitement(true),
            onFinish: () => {
                setTraitement(false);
                setConfirmation(false);
            },
        });
    };

    const colonnes: DataTableColumn<Repartition>[] = [
        {
            key: 'type',
            header: 'Type',
            cell: (r) => {
                const type = TYPES_REPARTITION[r.type];
                return <Badge variant={type?.variant ?? 'neutral'}>{type?.label ?? r.type}</Badge>;
            },
        },
        { key: 'periode', header: 'Mois / année', className: 'tabular-nums', cell: (r) => r.periode ?? <span className="text-muted">—</span> },
        { key: 'montant', header: 'Montant', className: 'whitespace-nowrap font-medium tabular-nums', cell: (r) => r.montant },
        { key: 'description', header: 'Description', cell: (r) => r.description ?? <span className="text-muted">—</span> },
    ];

    return (
        <AdminLayout title="Détail du paiement">
            <PageHeader
                title={`Paiement ${paiement.numero ?? ''}`.trim()}
                description={
                    <span className="flex flex-wrap items-center gap-2">
                        <StatutPaiementBadge statut={paiement.statut} label={paiement.statut_label} />
                        {paiement.resident.name ?? 'Résident inconnu'} · {paiement.montant}
                    </span>
                }
                actions={
                    <div className="flex flex-wrap items-center justify-end gap-2 print:hidden">
                        {urls.index && (
                            <Link href={urls.index} className={cn(buttonVariants({ variant: 'outline', size: 'sm' }), 'h-10 px-4')}>
                                <ArrowLeft {...ICON} aria-hidden /> Retour à la liste
                            </Link>
                        )}
                        <Button variant="secondary" size="sm" className="h-10 px-4" onClick={() => window.print()}>
                            <Printer {...ICON} aria-hidden /> Imprimer
                        </Button>
                        {urls.valider && (
                            <Button size="sm" className="h-10 px-4" onClick={() => setConfirmation(true)}>
                                <Check {...ICON} aria-hidden /> Valider le paiement
                            </Button>
                        )}
                    </div>
                }
            />

            <div className="grid gap-6 lg:grid-cols-3">
                <Carte titre="Informations générales" icone={<ReceiptText {...ICON} aria-hidden />} className="lg:col-span-2">
                    <dl className="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2 xl:grid-cols-3">
                        <Info terme="Numéro de paiement" valeur={paiement.numero} mono />
                        <Info terme="Montant" valeur={<span className="text-lg text-primary">{paiement.montant}</span>} />
                        <Info terme="Méthode de paiement" valeur={paiement.methode} />
                        <Info terme="Date de paiement" valeur={date} />
                        <Info terme="Date d’échéance" valeur={paiement.date_echeance ? dateFr(paiement.date_echeance) : null} />
                        <Info terme="Type de paiement" valeur={paiement.type} />
                        <Info terme="Référence de transaction" valeur={paiement.reference} mono />
                        <Info terme="Créé par" valeur={paiement.cree_par ?? 'Système'} />
                    </dl>
                    {paiement.notes && (
                        <div className="mt-5 rounded-brand bg-surface px-4 py-3">
                            <p className="text-xs text-muted">Notes</p>
                            <p className="mt-1 text-sm whitespace-pre-line text-gray-900">{paiement.notes}</p>
                        </div>
                    )}
                </Carte>

                <Carte titre="Résident" icone={<UserRound {...ICON} aria-hidden />}>
                    <dl className="grid grid-cols-1 gap-y-4 sm:grid-cols-2 lg:grid-cols-1">
                        <Info terme="Nom complet" valeur={paiement.resident.name} />
                        <Info terme="Matricule" valeur={paiement.resident.matricule} />
                        <Info terme="Téléphone" valeur={paiement.resident.telephone} />
                        <Info terme="E-mail" valeur={paiement.resident.email} />
                        <Info terme="Logement" valeur={[paiement.resident.residence, paiement.resident.chambre && `Chambre ${paiement.resident.chambre}`].filter(Boolean).join(' · ') || null} />
                    </dl>
                </Carte>
            </div>

            <section className="mt-6 overflow-hidden rounded-brand border bg-white">
                <div className="flex flex-wrap items-center justify-between gap-3 border-b px-5 py-4">
                    <h2 className="text-base font-medium text-gray-900">Répartition du paiement</h2>
                    <span className="text-sm text-muted">
                        Total : <strong className="font-medium text-gray-900">{paiement.montant}</strong>
                    </span>
                </div>
                <SimpleTable columns={colonnes} rows={paiement.repartitions} rowKey={(r) => r.id} emptyMessage="Aucune répartition disponible pour ce paiement." />
                {paiement.repartitions.length > 0 && (
                    <div className="flex flex-col gap-2 border-t bg-surface px-5 py-3 text-sm sm:flex-row sm:items-center sm:justify-end sm:gap-6">
                        <span>
                            Total réparti : <strong className="font-medium tabular-nums">{paiement.total_reparti}</strong>
                        </span>
                        {paiement.ecart && (
                            <span className="inline-flex items-center gap-1.5 text-amber-800">
                                <TriangleAlert className="size-4" aria-hidden /> Non réparti : <strong className="font-medium tabular-nums">{paiement.ecart}</strong>
                            </span>
                        )}
                    </div>
                )}
            </section>

            <Carte titre="Mois couverts par ce paiement" icone={<CalendarRange {...ICON} aria-hidden />} className="mt-6">
                {paiement.mois.length > 0 ? (
                    <div className="flex flex-wrap gap-2">
                        {paiement.mois.map((mois) => (
                            <Badge key={mois} variant="success" className="capitalize">
                                {mois}
                            </Badge>
                        ))}
                    </div>
                ) : (
                    <p className="text-sm text-muted">Aucun mois spécifié.</p>
                )}
            </Carte>

            {urls.valider && (
                <ConfirmDialog
                    open={confirmation}
                    onOpenChange={setConfirmation}
                    title="Valider ce paiement ?"
                    description={`Le paiement de ${paiement.montant} sera marqué comme payé à la date du jour.`}
                    confirmLabel="Valider le paiement"
                    confirmIcon={<Check className="size-4" aria-hidden />}
                    processing={traitement}
                    onConfirm={valider}
                />
            )}
        </AdminLayout>
    );
}

function Carte({ titre, icone, className, children }: { titre: string; icone: ReactNode; className?: string; children: ReactNode }) {
    return (
        <section className={cn('rounded-brand border bg-white p-5', className)}>
            <h2 className="mb-4 flex items-center gap-2 text-base font-medium text-gray-900">
                <span className="text-primary">{icone}</span>
                {titre}
            </h2>
            {children}
        </section>
    );
}

function Info({ terme, valeur, mono = false }: { terme: string; valeur: ReactNode; mono?: boolean }) {
    return (
        <div className="flex min-w-0 flex-col gap-0.5">
            <dt className="text-xs text-muted">{terme}</dt>
            <dd className={cn('truncate text-sm font-medium text-gray-900', mono && 'font-mono text-[13px]')}>{valeur || <span className="font-normal text-muted">—</span>}</dd>
        </div>
    );
}
