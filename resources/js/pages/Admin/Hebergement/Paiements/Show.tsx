import { Link, router } from '@inertiajs/react';
import { ArrowLeft, CalendarRange, Check, Printer, ReceiptText, Tag, UserRound } from 'lucide-react';
import { useState, type ReactNode } from 'react';
import { ConfirmDialog } from '@/components/feedback/confirm-dialog';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { StatutPaiementBadge } from '@/components/paiements/shared';
import { Badge } from '@/components/ui/badge';
import { Button, buttonVariants } from '@/components/ui/button';
import { PageHeader } from '@/components/ui/page-header';
import { dateFr } from '@/lib/text';
import { cn } from '@/lib/utils';

type Props = {
    paiement: {
        id: string;
        numero: string | null;
        montant: string;
        methode: string;
        type: string;
        type_code: string;
        statut: string;
        statut_label: string;
        date_paiement: string | null;
        date_echeance: string | null;
        reference: string | null;
        cree_par: string | null;
        notes: string | null;
        mois: string[];
        resident: { name: string | null; matricule: string | null; telephone: string | null; email: string | null; residence: string | null; chambre: string | null };
    };
    /** `recu` : reçu PDF du paiement (document dédié, une page A4). */
    urls: { index: string | null; valider: string | null; recu: string | null };
};

const NOMS_MOIS = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

/** « 2026-12 » ou « 12-2026 » → « décembre 2026 » (valeur inchangée si le format n'est pas reconnu). */
function moisEnLettres(valeur: string): string {
    const texte = valeur.trim();
    const anneeDabord = texte.match(/^(\d{4})[-/](\d{1,2})$/);
    const moisDabord = texte.match(/^(\d{1,2})[-/](\d{4})$/);
    const [annee, mois] = anneeDabord ? [anneeDabord[1], anneeDabord[2]] : moisDabord ? [moisDabord[2], moisDabord[1]] : [null, null];
    const nom = mois ? NOMS_MOIS[Number(mois) - 1] : undefined;
    return nom ? `${nom} ${annee}` : valeur;
}

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

    return (
        <AdminLayout title="Détail du paiement">
            <PageHeader
                title={`Paiement ${paiement.numero ?? ''}`.trim()}
                // Statut affiché seulement s'il demande attention (en attente, annulé…) : pas de badge « Validé ».
                description={paiement.statut !== 'valide' ? <StatutPaiementBadge statut={paiement.statut} label={paiement.statut_label} /> : undefined}
                actions={
                    <div className="flex flex-wrap items-center justify-end gap-2 print:hidden">
                        {urls.index && (
                            <Link href={urls.index} className={cn(buttonVariants({ variant: 'outline', size: 'sm' }), 'h-10 px-4')}>
                                <ArrowLeft {...ICON} aria-hidden /> Retour à la liste
                            </Link>
                        )}
                        {urls.recu && (
                            <a href={urls.recu} target="_blank" rel="noopener" className={cn(buttonVariants({ variant: 'secondary', size: 'sm' }), 'h-10 px-4')}>
                                <Printer {...ICON} aria-hidden /> Imprimer le reçu
                            </a>
                        )}
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
                    {/* Type de paiement mis en évidence : c'est l'information qui qualifie le paiement (loyer, caution, avance…). */}
                    <div className="mb-5 flex items-center gap-3 rounded-brand border border-[#86BB46]/40 bg-[#EBF8DE] px-4 py-3">
                        <span className="flex size-9 shrink-0 items-center justify-center rounded-brand bg-white text-primary">
                            <Tag {...ICON} aria-hidden />
                        </span>
                        <div className="min-w-0">
                            <p className="text-xs text-muted">Type de paiement</p>
                            <p className="truncate text-lg font-medium text-primary">{paiement.type || '—'}</p>
                        </div>
                    </div>
                    <dl className="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2 xl:grid-cols-3">
                        <Info terme="Numéro de paiement" valeur={paiement.numero} mono />
                        <Info terme="Montant" valeur={<span className="text-lg text-primary">{paiement.montant}</span>} />
                        <Info terme="Méthode de paiement" valeur={paiement.methode} />
                        <Info terme="Date de paiement" valeur={date} />
                        <Info terme="Date d’échéance" valeur={paiement.date_echeance ? dateFr(paiement.date_echeance) : null} />
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

            <Carte titre="Mois couverts par ce paiement" icone={<CalendarRange {...ICON} aria-hidden />} className="mt-6">
                {paiement.mois.length > 0 ? (
                    <div className="flex flex-wrap gap-2">
                        {paiement.mois.map((mois) => (
                            <Badge key={mois} variant="success" className="capitalize">
                                {moisEnLettres(mois)}
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
