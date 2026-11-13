import { Gavel, ShieldCheck } from 'lucide-react';
import { Vide } from '@/components/etudiant/panneau';
import { ICON } from '@/components/layout/app-sidebar';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';
import { cn } from '@/lib/utils';
import { points } from '@/schemas/parametrage-sanctions';

type Ton = 'success' | 'warning' | 'danger' | 'neutral';

/** Sanction qui vise l'étudiant (`App\Services\EtudiantConduite`). */
export type SanctionEtudiant = {
    id: string;
    numero: string;
    type: string;
    date: string | null;
    periode: string | null;
    motif: string | null;
    points: number;
    /** Faux pour une sanction annulée : ses points sont restitués. */
    compte: boolean;
    statut: { cle: string; libelle: string; ton: Ton };
};

/** Note de conduite de l'étudiant : note initiale du paramétrage − points retirés par ses sanctions. */
export type Conduite = {
    note: number;
    initiale: number;
    retires: number;
    ton: 'success' | 'warning' | 'danger';
    sanctions: SanctionEtudiant[];
};

const TONS_JAUGE = { success: 'primary', warning: 'accent', danger: 'danger' } as const;

/** Note de conduite et jauge, avec le total des points retirés. */
export function NoteConduite({ conduite, className }: { conduite: Conduite; className?: string }) {
    const part = conduite.initiale > 0 ? (conduite.note / conduite.initiale) * 100 : 100;

    return (
        <div className={cn('flex flex-col gap-3 rounded-brand border bg-white p-4', className)}>
            <div className="flex items-center gap-3">
                <span className="grid size-11 shrink-0 place-items-center rounded-brand bg-soft text-primary" aria-hidden>
                    <ShieldCheck {...ICON} />
                </span>
                <div className="min-w-0">
                    <p className="text-xs text-muted">Ma note de conduite</p>
                    <p className="text-2xl font-medium text-gray-900 tabular-nums">
                        {points(conduite.note)}
                        <span className="text-base text-muted"> / {points(conduite.initiale)}</span>
                    </p>
                </div>
            </div>
            <Progress value={part} tone={TONS_JAUGE[conduite.ton]} label={`Note de conduite : ${points(conduite.note)} sur ${points(conduite.initiale)}`} />
            <p className="text-sm text-muted">
                {conduite.retires > 0
                    ? `${points(conduite.retires)} point(s) retiré(s) par vos sanctions.`
                    : 'Aucun point retiré : votre conduite est irréprochable.'}
            </p>
        </div>
    );
}

/** Historique des sanctions et des retraits de points (les plus récentes d'abord). */
export function HistoriqueSanctions({ sanctions }: { sanctions: SanctionEtudiant[] }) {
    if (sanctions.length === 0) return <Vide icone={ShieldCheck}>Aucune sanction ne vous a été appliquée.</Vide>;

    return (
        <ul className="-mx-2 divide-y">
            {sanctions.map((sanction) => (
                <li key={sanction.id} className="flex flex-wrap items-start gap-3 px-2 py-3">
                    <span className={cn('grid size-10 shrink-0 place-items-center rounded-brand', sanction.compte ? 'bg-red-50 text-red-700' : 'bg-surface text-muted')} aria-hidden>
                        <Gavel {...ICON} />
                    </span>
                    <span className="min-w-0 flex-1">
                        <strong className="block text-sm font-medium text-gray-900">{sanction.type}</strong>
                        <small className="block text-xs text-muted">
                            {[sanction.numero, sanction.date && `Décision du ${sanction.date}`, sanction.periode].filter(Boolean).join(' · ')}
                        </small>
                        {sanction.motif && <span className="mt-1 block text-sm whitespace-pre-line text-gray-700">{sanction.motif}</span>}
                    </span>
                    <span className="flex shrink-0 flex-col items-end gap-1.5">
                        <span className={cn('text-sm font-medium tabular-nums', sanction.compte ? 'text-red-700' : 'text-muted line-through')}>
                            −{points(sanction.points)} pt{sanction.points > 1 ? 's' : ''}
                        </span>
                        <Badge variant={sanction.statut.ton}>{sanction.compte ? sanction.statut.libelle : 'Annulée · points restitués'}</Badge>
                    </span>
                </li>
            ))}
        </ul>
    );
}
