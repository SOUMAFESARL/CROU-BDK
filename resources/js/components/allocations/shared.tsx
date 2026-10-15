import { Archive, CalendarClock, Check, CirclePlay, Info, X, Zap } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { nombre } from '@/lib/text';

export type CampagneAllocation = {
    id: string;
    annee: string;
    description: string | null;
    statut: string | null;
    statut_label: string;
    periode_debut: string | null;
    periode_fin: string | null;
};

export type StatsAllocation = {
    demandes_acceptees: number;
    assignations_confirmees: number;
    assignations_en_attente: number;
    total_assignations: number;
    sans_chambre: number;
    taux_allocation: number;
    par_type: { nom: string; total: number; occupees: number; disponibles: number }[];
};

export type Classement = {
    reservations: number;
    lignes: {
        rang: number | null;
        name: string | null;
        score: number | null;
        chambre: string | null;
        raison: string | null;
        bareme: string | number | null;
        priorite: string | number | null;
    }[];
};

const STATUTS = {
    active: { variant: 'success', icone: CirclePlay },
    planifiee: { variant: 'warning', icone: CalendarClock },
    terminee: { variant: 'neutral', icone: Archive },
} as const;

export function StatutCampagne({ campagne }: { campagne: CampagneAllocation }) {
    const statut = STATUTS[campagne.statut as keyof typeof STATUTS];
    const Icone = statut?.icone ?? Info;

    return (
        <Badge variant={statut?.variant ?? 'neutral'}>
            <Icone className="size-3.5" aria-hidden />
            {campagne.statut_label}
        </Badge>
    );
}

/** Pourcentage au format français, sans zéros inutiles (« 12,5 % »). */
export const pourcent = (valeur: number) => `${nombre(valeur, 2)} %`;

/** Classement utilisé par la dernière attribution automatique (affiché juste après l'action). */
export function ClassementAttribution({ classement }: { classement: Classement | null }) {
    if (!classement) return null;

    return (
        <section className="mt-6 overflow-hidden rounded-brand border bg-white">
            <div className="flex flex-wrap items-center justify-between gap-2 border-b px-4 py-3">
                <h2 className="flex items-center gap-2 text-base font-medium text-gray-900">
                    <span className="flex size-8 items-center justify-center rounded-brand bg-[#FEEBC0] text-[#6B3F00]" aria-hidden>
                        <Zap size={18} strokeWidth={1.25} />
                    </span>
                    Classement utilisé lors de l’attribution
                </h2>
                <span className="text-sm text-muted">
                    {classement.reservations} réservation(s) sur {classement.lignes.length} candidat(s)
                </span>
            </div>
            <Table>
                <TableHeader>
                    <TableRow className="hover:bg-transparent">
                        <TableHead className="w-16">Rang</TableHead>
                        <TableHead>Candidat</TableHead>
                        <TableHead>Score</TableHead>
                        <TableHead>Résultat</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {classement.lignes.map((ligne, index) => (
                        <TableRow key={`${ligne.rang}-${index}`}>
                            <TableCell className="tabular-nums">{ligne.rang ?? '—'}</TableCell>
                            <TableCell className="font-medium text-gray-900">{ligne.name}</TableCell>
                            <TableCell className="whitespace-nowrap tabular-nums">{ligne.score !== null ? `${nombre(ligne.score, 2)} / 100` : '—'}</TableCell>
                            <TableCell>
                                {ligne.chambre ? (
                                    <Badge variant="success">
                                        <Check className="size-3.5" aria-hidden /> Chambre {ligne.chambre} réservée
                                    </Badge>
                                ) : (
                                    <Badge variant="danger">
                                        <X className="size-3.5" aria-hidden /> {ligne.raison ?? 'Non attribué'}
                                    </Badge>
                                )}
                                <span className="mt-1 block text-xs text-muted">
                                    {ligne.bareme ? `Barème #${ligne.bareme} · Priorité ${ligne.priorite}` : 'Classement provisoire'}
                                </span>
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </section>
    );
}

/** Lien-bouton secondaire (bordure grise) vers une page non Inertia ou un téléchargement. */
export const lienContour =
    'inline-flex h-10 items-center gap-2 rounded-brand border border-gray-300 bg-white px-4 text-sm font-medium text-muted transition-colors hover:bg-surface hover:text-primary';
