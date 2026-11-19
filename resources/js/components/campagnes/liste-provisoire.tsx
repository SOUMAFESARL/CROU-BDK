import { Link } from '@inertiajs/react';
import { Eye, ListOrdered } from 'lucide-react';
import { useLayoutEffect, useRef, useState, type ReactNode } from 'react';
import { DataTable, type DataTableColumn } from '@/components/data-table/data-table';
import { EtatDossierBadge } from '@/components/dossier/etat-dossier-badge';
import { ICON } from '@/components/layout/app-sidebar';
import { Badge } from '@/components/ui/badge';
import { buttonVariants } from '@/components/ui/button';
import { initiales, nombre } from '@/lib/text';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types/global';

/** Onglets des demandes recevables : la liste des demandes, puis les listes provisoires par volet. */
export type ListeRecevables = 'demandes' | 'admission' | 'readmission';

export type LigneClassement = {
    id: string;
    rang: number;
    name: string;
    numero_demande: string | null;
    niveau: string | null;
    etat_dossier: string | null;
    /** Envoyé seulement avec le droit de voir les données personnelles. */
    age: number | null;
    /** Admission : note du bac (sur 400) ou crédits validés (sur 60) selon le public. */
    merite: { libelle: string; valeur: number; max: number; pourcentage: number } | null;
    ville_bac: string | null;
    hors_ville: boolean | null;
    /** Réadmission. */
    categorie: { code: string; libelle: string } | null;
    boursier: boolean | null;
    moyenne: number | null;
    credits: number | null;
    url: string | null;
};

export type Classement = {
    liste: ListeRecevables;
    /** Onglets affichés : la liste des demandes, puis une liste provisoire par volet ouvert par la campagne. */
    onglets: ListeRecevables[];
    campagne: string | null;
    compteurs: Record<ListeRecevables, number>;
    lignes: Paginated<LigneClassement> | null;
    colonnes: { age: boolean };
    urls: Partial<Record<ListeRecevables, string | null>>;
};

const ONGLETS: { cle: ListeRecevables; label: string }[] = [
    { cle: 'demandes', label: 'Liste des demandes' },
    { cle: 'admission', label: 'Liste provisoire admissions' },
    { cle: 'readmission', label: 'Liste provisoire réadmissions' },
];

/**
 * Onglets alignés à droite du titre « Demandes recevables » (navigation Inertia, l'onglet ouvert est dans l'URL).
 * `actif` : onglet choisi, mis à jour dès le clic (avant la réponse du serveur) pour que le soulignement glisse
 * aussitôt vers lui ; `onSelect` le signale à la page.
 */
export function OngletsRecevables({ classement, actif, onSelect }: { classement: Classement; actif: ListeRecevables; onSelect: (onglet: ListeRecevables) => void }) {
    const navRef = useRef<HTMLElement>(null);
    const [position, setPosition] = useState<{ left: number; top: number; width: number } | null>(null);

    // Soulignement : suit l'onglet actif (et la largeur de la liste).
    useLayoutEffect(() => {
        const nav = navRef.current;
        if (!nav) return;
        const mesurer = () => {
            const onglet = nav.querySelector<HTMLElement>(`[data-onglet="${actif}"]`);
            // Bas de l'onglet (les onglets peuvent passer à la ligne sur un écran étroit).
            setPosition(onglet ? { left: onglet.offsetLeft, top: onglet.offsetTop + onglet.offsetHeight - 2, width: onglet.offsetWidth } : null);
        };
        mesurer();
        const observateur = new ResizeObserver(mesurer);
        observateur.observe(nav);
        return () => observateur.disconnect();
    }, [actif]);

    return (
        <nav ref={navRef} aria-label="Demandes recevables" className="relative -mb-px ml-auto flex max-w-full flex-wrap items-center gap-1">
            {ONGLETS.filter(({ cle }) => classement.onglets.includes(cle)).map(({ cle, label }) => {
                const courant = cle === actif;
                const url = classement.urls[cle];
                const classes = cn(
                    'inline-flex h-10 shrink-0 items-center gap-2 px-3 text-sm whitespace-nowrap transition-colors duration-300',
                    'focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none focus-visible:ring-inset',
                    courant ? 'font-medium text-primary' : 'text-muted hover:text-primary',
                );
                const contenu = (
                    <>
                        {label}
                        <span className={cn('rounded px-1.5 text-xs tabular-nums transition-colors duration-300', courant ? 'bg-soft text-primary' : 'bg-gray-100 text-muted')}>
                            {nombre(classement.compteurs[cle])}
                        </span>
                    </>
                );

                return cle === classement.liste || !url ? (
                    <span key={cle} data-onglet={cle} aria-current={cle === classement.liste ? 'page' : undefined} className={classes}>
                        {contenu}
                    </span>
                ) : (
                    // `preserveState` : la page n'est pas remontée, le soulignement glisse d'un onglet à l'autre.
                    <Link key={cle} data-onglet={cle} href={url} preserveState preserveScroll onClick={() => onSelect(cle)} className={classes}>
                        {contenu}
                    </Link>
                );
            })}
            {position && (
                <span
                    aria-hidden
                    className="pointer-events-none absolute top-0 left-0 h-0.5 bg-primary transition-[transform,width] duration-300 ease-out motion-reduce:transition-none"
                    style={{ width: position.width, transform: `translate(${position.left}px, ${position.top}px)` }}
                />
            )}
        </nav>
    );
}

/** Tableau d'une liste provisoire (admission ou réadmission), dans l'ordre du classement. */
export function TableauClassement({ classement, toolbar, filtre }: { classement: Classement; toolbar: ReactNode; filtre: boolean }) {
    if (!classement.lignes) return null;
    const readmission = classement.liste === 'readmission';
    const vide = (valeur: ReactNode) => valeur ?? <span className="text-muted">—</span>;

    const colonnes: DataTableColumn<LigneClassement>[] = [
        { key: 'rang', header: 'Rang', headClassName: 'w-16', className: 'font-medium text-gray-900 tabular-nums', cell: (l) => l.rang },
        {
            key: 'etudiant',
            header: 'Étudiant',
            cell: (l) => (
                <div className="flex min-w-48 items-center gap-3">
                    <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-soft text-xs font-medium text-primary" aria-hidden>
                        {initiales(l.name)}
                    </span>
                    <div className="min-w-0">
                        <p className="font-medium text-gray-900">{l.name}</p>
                        {l.numero_demande && <p className="text-xs text-muted">Demande #{l.numero_demande}</p>}
                    </div>
                </div>
            ),
        },
        { key: 'niveau', header: 'Niveau', cell: (l) => vide(l.niveau) },
        ...(readmission
            ? ([
                  {
                      key: 'categorie',
                      header: 'Catégorie',
                      cell: (l) =>
                          l.categorie ? (
                              <Badge variant={l.categorie.code === 'C1' ? 'success' : 'neutral'} title={l.categorie.libelle}>
                                  {l.categorie.code} · {l.categorie.libelle}
                              </Badge>
                          ) : (
                              vide(null)
                          ),
                  },
                  { key: 'boursier', header: 'Boursier', cell: (l) => (l.boursier ? <Badge variant="info">Oui</Badge> : <span className="text-muted">Non</span>) },
                  {
                      key: 'merite',
                      header: 'Moyenne',
                      className: 'whitespace-nowrap tabular-nums',
                      cell: (l) => (
                          <>
                              {l.moyenne === null ? vide(null) : `${nombre(l.moyenne, 2)}/20`}
                              {l.credits !== null && <p className="text-xs text-muted">{nombre(l.credits, 2)} % des crédits</p>}
                          </>
                      ),
                  },
              ] satisfies DataTableColumn<LigneClassement>[])
            : ([
                  {
                      key: 'merite',
                      header: 'Mérite',
                      className: 'whitespace-nowrap tabular-nums',
                      cell: (l) =>
                          l.merite ? (
                              <>
                                  {nombre(l.merite.valeur, 2)}/{l.merite.max}
                                  <p className="text-xs text-muted">
                                      {l.merite.libelle} · {nombre(l.merite.pourcentage, 2)} %
                                  </p>
                              </>
                          ) : (
                              vide(null)
                          ),
                  },
                  {
                      key: 'ville_bac',
                      header: 'Ville du bac',
                      cell: (l) => (
                          <>
                              {vide(l.ville_bac)}
                              {l.hors_ville && <p className="text-xs text-primary">Hors ville de référence</p>}
                          </>
                      ),
                  },
              ] satisfies DataTableColumn<LigneClassement>[])),
        ...(classement.colonnes.age
            ? ([{ key: 'age', header: 'Âge', className: 'whitespace-nowrap tabular-nums', cell: (l) => (l.age === null ? vide(null) : `${l.age} ans`) }] satisfies DataTableColumn<LigneClassement>[])
            : []),
        { key: 'etat', header: 'État du dossier', cell: (l) => <EtatDossierBadge etat={l.etat_dossier} /> },
        {
            key: 'actions',
            header: <span className="sr-only">Actions</span>,
            className: 'text-right',
            cell: (l) =>
                l.url && (
                    <Link href={l.url} className={cn(buttonVariants({ variant: 'secondary', size: 'sm' }), 'whitespace-nowrap')}>
                        <Eye className="size-4" aria-hidden /> Suivre le dossier
                    </Link>
                ),
        },
    ];

    return (
        <DataTable
            columns={colonnes}
            data={classement.lignes}
            rowKey={(l) => l.id}
            rowHref={(l) => l.url}
            toolbar={toolbar}
            emptyMessage={
                <span className="flex flex-col items-center gap-2">
                    <ListOrdered {...ICON} aria-hidden />
                    {filtre
                        ? 'Aucune demande ne correspond à cette recherche.'
                        : `Aucune demande recevable de ${readmission ? 'réadmission' : 'admission'} à classer${classement.campagne ? ` pour la campagne ${classement.campagne}` : ''}.`}
                </span>
            }
        />
    );
}
