import { ChevronLeft, ChevronRight } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Variante } from '@/components/vie-residentielle/shared';
import { montant } from '@/lib/systeme';
import { nombre, sansAccents } from '@/lib/text';

export type Option = { value: string; label: string };
export type CiteOption = { id: string; name: string };

export type StatutDemande = 'en_attente' | 'approuvee' | 'cloturee' | 'refusee';

export const STATUTS_DEMANDE: Record<StatutDemande, { label: string; variante: Variante; couleur: string }> = {
    en_attente: { label: 'À traiter', variante: 'warning', couleur: '#FAAB04' },
    approuvee: { label: 'Approuvée', variante: 'success', couleur: '#006400' },
    cloturee: { label: 'Clôturée', variante: 'info', couleur: '#0B3A6E' },
    refusee: { label: 'Refusée / annulée', variante: 'neutral', couleur: '#54595F' },
};

export function StatutDemandeBadge({ statut }: { statut: StatutDemande }) {
    const s = STATUTS_DEMANDE[statut];
    return <Badge variant={s.variante}>{s.label}</Badge>;
}

export type Decompte = {
    preavis_requis: number;
    preavis_effectif: number;
    penalite_preavis: number;
    degradations: { piece: string; element: string; regle: string; montant: number }[];
    total_degradations: number;
    total_retenues: number;
    impute_caution: number;
    solde_debiteur: number;
    caution_a_restituer: number;
    caution_restituee: number;
    date_restitution: string | null;
    mode_restitution: string | null;
};

export type Demande = {
    id: string;
    etudiant_id: string;
    etudiant: string;
    matricule: string | null;
    chambre: string | null;
    cite: string | null;
    cite_id: string | null;
    batiment: string | null;
    date_demande: string | null;
    date_sortie: string | null;
    motif: string | null;
    observations: string | null;
    statut: StatutDemande;
    decision: string | null;
    motif_decision: string | null;
    expulsion: boolean;
    expulsion_active: boolean;
    decompte: Decompte | null;
    urls: { decision: string | null; etat_des_lieux: string | null; pdf: string | null; restitution: string | null };
};

export const fcfa = (valeur: number | null | undefined) => montant(valeur);

/** `aaaa-mm-jj` du jour (heure locale). */
export const jourIso = (date = new Date()) =>
    `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;

/** Nombre de jours entre aujourd'hui et une date `aaaa-mm-jj` (négatif si passée). */
export const joursAvant = (date: string | null) => {
    if (!date) return null;
    const [a, m, j] = date.split('-').map(Number);
    const aujourd = new Date();
    aujourd.setHours(0, 0, 0, 0);
    return Math.round((new Date(a, m - 1, j).getTime() - aujourd.getTime()) / 86_400_000);
};

/** Recherche locale sans accents sur plusieurs champs. */
export const correspond = (recherche: string, ...champs: (string | null | undefined)[]) => {
    const terme = sansAccents(recherche.trim());
    return !terme || sansAccents(champs.filter(Boolean).join(' ')).includes(terme);
};

/** Pagination locale (listes déjà chargées et filtrées dans la page), revient à la page 1 quand la liste change. */
export function usePaginationLocale<T>(lignes: T[], parPage = 10) {
    const [page, setPage] = useState(1);
    const derniere = Math.max(1, Math.ceil(lignes.length / parPage));
    useEffect(() => setPage(1), [lignes]);
    const courante = Math.min(page, derniere);
    const visibles = useMemo(() => lignes.slice((courante - 1) * parPage, courante * parPage), [lignes, courante, parPage]);

    return { visibles, page: courante, derniere, total: lignes.length, parPage, setPage };
}

export function PaginationLocale({ page, derniere, total, parPage, setPage }: Omit<ReturnType<typeof usePaginationLocale>, 'visibles'>) {
    if (total === 0) return null;
    const debut = (page - 1) * parPage + 1;
    const fin = Math.min(page * parPage, total);

    return (
        <div className="flex flex-col items-center justify-between gap-2 border-t px-4 py-3 text-sm text-muted sm:flex-row">
            <span>
                {nombre(debut)} à {nombre(fin)} sur {nombre(total)}
            </span>
            {derniere > 1 && (
                <div className="flex items-center gap-1">
                    <Button variant="ghost" size="sm" className="h-9" disabled={page <= 1} onClick={() => setPage(page - 1)} aria-label="Page précédente">
                        <ChevronLeft className="size-4" aria-hidden /> Précédent
                    </Button>
                    <span className="px-2 tabular-nums">
                        {page} / {derniere}
                    </span>
                    <Button variant="ghost" size="sm" className="h-9" disabled={page >= derniere} onClick={() => setPage(page + 1)} aria-label="Page suivante">
                        Suivant <ChevronRight className="size-4" aria-hidden />
                    </Button>
                </div>
            )}
        </div>
    );
}
