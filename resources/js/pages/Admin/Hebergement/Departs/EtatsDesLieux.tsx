import { ArrowLeft } from 'lucide-react';
import { useState } from 'react';
import { EtatsDesLieuxPanel, type DonneesEtatsDesLieux } from '@/components/departs/etats-des-lieux-panel';
import type { CiteOption } from '@/components/departs/shared';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { buttonVariants } from '@/components/ui/button';
import { PageHeader } from '@/components/ui/page-header';
import { cn } from '@/lib/utils';

type Props = Omit<DonneesEtatsDesLieux, 'urls'> & {
    cites: CiteOption[];
    urls: { store: string | null; departs: string | null };
};

/** États des lieux par résident (la même liste est le premier onglet de la page Départs). */
export default function EtatsDesLieux({ residents, cites, grille, preavisJours, urls }: Props) {
    // `?etudiant=` ouvre directement l'assistant de sortie.
    const [cible, setCible] = useState(() => new URLSearchParams(window.location.search).get('etudiant'));

    return (
        <AdminLayout title="États des lieux">
            <PageHeader
                title="États des lieux"
                description="L’état des lieux d’entrée est établi par l’étudiant puis signé par le CROU. L’état de sortie est établi par le CROU après approbation de la demande de sortie ; il clôture le séjour."
                actions={
                    urls.departs && (
                        <a href={urls.departs} className={cn(buttonVariants({ variant: 'outline', size: 'sm' }), 'h-10 px-4')}>
                            <ArrowLeft {...ICON} aria-hidden /> Demandes de sortie
                        </a>
                    )
                }
            />
            <EtatsDesLieuxPanel residents={residents} cites={cites} grille={grille} preavisJours={preavisJours} urls={urls} cible={cible} onCibleOuverte={() => setCible(null)} />
        </AdminLayout>
    );
}
