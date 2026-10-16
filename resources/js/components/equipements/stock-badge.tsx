import { Check, Package, TriangleAlert, X } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import { STATUTS_STOCK, type StatutStock } from './types';

const ICONES = { disponible: Check, faible: TriangleAlert, rupture: X };

export function StockBadge({ statut }: { statut: StatutStock }) {
    const { label, variant } = STATUTS_STOCK[statut];
    const Icone = ICONES[statut];

    return (
        <Badge variant={variant}>
            <Icone className="size-3.5" strokeWidth={1.5} aria-hidden />
            {label}
        </Badge>
    );
}

/** Vignette d'un équipement : photo ou icône sur fond orangé. */
export function EquipementThumb({ photo, className }: { photo: string | null; className?: string }) {
    return photo ? (
        <img src={photo} alt="" loading="lazy" className={cn('size-10 shrink-0 rounded-brand border object-cover', className)} />
    ) : (
        <span className={cn('flex size-10 shrink-0 items-center justify-center rounded-brand bg-[#FFF4D6] text-[#A15C00]', className)} aria-hidden>
            <Package size={18} strokeWidth={1.25} />
        </span>
    );
}
