import { BedDouble, Building, Building2, type LucideIcon } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import type { EtatChambre, Niveau, ParcItem } from './types';

export const nombre = new Intl.NumberFormat('fr-FR');

export const TYPE_ICONS: Record<Niveau, LucideIcon> = { cite: Building2, batiment: Building, chambre: BedDouble };

export const TYPE_LABELS: Record<Niveau, string> = { cite: 'Cité', batiment: 'Bâtiment', chambre: 'Chambre' };

/** Une cité, un bâtiment ou une chambre est actif tant qu'il n'a pas été désactivé (`toggleStatus`). */
export const estActif = (item: Pick<ParcItem, 'statut'>) => item.statut !== 'desactive';

export const STATUTS: Record<string, string> = {
    actif: 'Actif',
    ouvert: 'Ouvert',
    ferme: 'Fermé',
    en_renovation: 'En rénovation',
    en_construction: 'En construction',
    desactive: 'Désactivé',
};

export function StatutBadge({ statut }: { statut: string }) {
    const actif = statut !== 'desactive';

    return (
        <Badge variant={actif ? 'success' : 'neutral'}>
            <span className={cn('size-1.5 rounded-full', actif ? 'bg-primary' : 'bg-gray-400')} aria-hidden />
            {STATUTS[statut] ?? statut}
        </Badge>
    );
}

/** États d'une chambre : libellé + couleurs (badge, tuile du plan, légende). */
export const ETATS: Record<EtatChambre, { label: string; badge: string; tile: string; dot: string }> = {
    disponible: {
        label: 'Disponible',
        badge: 'bg-soft text-primary',
        tile: 'border-secondary/60 bg-soft text-primary hover:border-primary',
        dot: 'bg-secondary',
    },
    occupee: {
        label: 'Occupée',
        badge: 'bg-primary text-white',
        tile: 'border-primary bg-primary text-white hover:bg-primary-hover',
        dot: 'bg-primary',
    },
    reservee: {
        label: 'Réservée',
        badge: 'bg-amber-50 text-amber-800',
        tile: 'border-accent/70 bg-amber-50 text-amber-900 hover:border-accent',
        dot: 'bg-accent',
    },
    maintenance: {
        label: 'Maintenance',
        badge: 'bg-red-50 text-red-700',
        tile: 'border-red-200 bg-red-50 text-red-700 hover:border-red-400',
        dot: 'bg-red-400',
    },
    hors_service: {
        label: 'Hors service',
        badge: 'bg-red-50 text-red-700',
        tile: 'border-red-200 bg-red-50 text-red-700 hover:border-red-400',
        dot: 'bg-red-400',
    },
};

export function EtatBadge({ etat }: { etat: EtatChambre | null }) {
    if (!etat || !ETATS[etat]) {
        return <Badge variant="neutral">Non renseigné</Badge>;
    }

    return <Badge className={ETATS[etat].badge}>{ETATS[etat].label}</Badge>;
}

export const pluriel = (n: number, singulier: string, plurielForme = `${singulier}s`) =>
    `${nombre.format(n)} ${n > 1 ? plurielForme : singulier}`;
