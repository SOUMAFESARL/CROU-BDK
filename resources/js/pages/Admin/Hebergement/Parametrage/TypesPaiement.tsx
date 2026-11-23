import { HandCoins, Wallet } from 'lucide-react';
import { ICON } from '@/components/layout/app-sidebar';
import { ReferentielPage, type ChampReferentiel, type ElementReferentiel } from '@/components/parametrage-hebergement/referentiel-page';
import { Badge } from '@/components/ui/badge';
import { buttonVariants } from '@/components/ui/button';
import { nombre } from '@/lib/text';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types/global';

type TypePaiement = ElementReferentiel & { statut: 'actif' | 'inactif'; systeme: boolean; paiements: number };

type Props = {
    types: Paginated<TypePaiement>;
    stats: { total: number; utilises: number; inutilises: number };
    filters: { q: string };
    urls: { index: string; store: string | null; paiements: string | null };
};

const CHAMPS: ChampReferentiel[] = [
    { cle: 'name', label: 'Libellé', max: 100, requis: 'Renseignez le libellé du type de paiement.', placeholder: 'Ex. Frais de dossier' },
    { cle: 'description', label: 'Description', type: 'zone', max: 2000, placeholder: 'Description', hint: 'Facultative : ce que couvre ce paiement.' },
    {
        cle: 'statut',
        label: 'Statut',
        type: 'liste',
        max: 10,
        defaut: 'actif',
        requis: 'Choisissez le statut du type de paiement.',
        placeholder: 'Sélectionner un statut',
        hint: 'Seuls les types actifs sont proposés dans « Nouveau paiement ». Loyer, Caution, Pénalité et Autre restent toujours actifs.',
        options: [
            { value: 'actif', label: 'Actif' },
            { value: 'inactif', label: 'Inactif' },
        ],
    },
];

/** Types de paiement proposés au champ « Type de paiement » du formulaire « Nouveau paiement » (Paiements). */
export default function TypesPaiement({ types, stats, filters, urls }: Props) {
    return (
        <ReferentielPage
            titre="Types de paiement"
            description="Natures de paiement proposées dans le formulaire « Nouveau paiement ». Un type utilisé par des paiements ne peut pas être supprimé."
            singulier="type de paiement"
            un="un type de paiement"
            champs={CHAMPS}
            enteteNom="Libellé"
            recherche="Rechercher un libellé ou une description"
            elements={types}
            stats={stats}
            kpis={{ total: 'Types enregistrés', utilises: 'Proposés à la saisie', inutilises: 'Désactivés' }}
            filters={filters}
            urls={urls}
            icone={<Wallet {...ICON} />}
            usage={(t) => ({ total: t.paiements, libelle: 'paiement(s)' })}
            conseilSuppression={(t) =>
                t.systeme
                    ? 'Il ne peut être supprimé que s’il n’est utilisé par aucun paiement.'
                    : 'Désactivez-le plutôt : il ne sera plus proposé à la saisie et l’historique restera lisible.'
            }
            actions={
                urls.paiements && (
                    <a href={urls.paiements} className={cn(buttonVariants({ variant: 'secondary', size: 'sm' }), 'h-10')}>
                        <HandCoins className="size-4" aria-hidden /> Paiements
                    </a>
                )
            }
            colonnes={[
                {
                    key: 'statut',
                    header: 'Statut',
                    className: 'whitespace-nowrap',
                    cell: (t) => (t.statut === 'actif' ? <Badge variant="success">Actif</Badge> : <Badge variant="neutral">Inactif</Badge>),
                },
                {
                    key: 'paiements',
                    header: 'Paiements',
                    className: 'whitespace-nowrap text-sm',
                    cell: (t) => (t.paiements === 0 ? <span className="text-muted">Aucun</span> : <span className="text-gray-900">{nombre(t.paiements)}</span>),
                },
            ]}
        />
    );
}
