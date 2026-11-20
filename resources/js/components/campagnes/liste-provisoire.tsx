import { Link } from '@inertiajs/react';
import { CircleCheck, CircleX, EllipsisVertical, Eye, FileCheck2, FileClock, FileX2, ListOrdered } from 'lucide-react';
import { useLayoutEffect, useRef, useState, type ReactNode } from 'react';
import { DataTable, type DataTableColumn } from '@/components/data-table/data-table';
import { ConfirmDialog } from '@/components/feedback/confirm-dialog';
import { ICON } from '@/components/layout/app-sidebar';
import { Badge } from '@/components/ui/badge';
import { Button, buttonVariants } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Field } from '@/components/ui/field';
import { Textarea } from '@/components/ui/textarea';
import { envoyer } from '@/lib/inertia-form';
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
    /** Classé devant les non-boursiers quand la priorité aux boursiers est retenue (paramétrage général). */
    boursier: boolean | null;
    /** Réadmission. */
    categorie: { code: string; libelle: string } | null;
    moyenne: number | null;
    credits: number | null;
    /** État des pièces du dossier ; envoyé seulement avec le droit de voir les documents (null : aucune pièce demandée ni déposée). */
    documents: PiecesDossier | null;
    url: string | null;
    /**
     * Décision depuis la liste provisoire (`admin.dossiers.transition`) : validation (dossier « Recevable », la demande
     * passe dans la « Liste validée ») et rejet (« Refusé », motif obligatoire, la demande passe dans « Demandes refusées »).
     * Sur la liste validée, seul le rejet est proposé. Null sans le droit de changer l'état du dossier ou si aucune n'est permise.
     */
    decision: { url: string; valider: boolean; rejeter: boolean } | null;
};

export type PiecesDossier = {
    statut: 'valide' | 'incomplet' | 'en_verification';
    valides: number;
    rejetes: number;
    en_verification: number;
    absents: number;
};

export type Classement = {
    liste: ListeRecevables;
    /** « Liste validée » : demandes validées, un onglet par volet (sans la liste des demandes). */
    validee: boolean;
    /** Onglets affichés : la liste des demandes, puis une liste provisoire par volet ouvert par la campagne. */
    onglets: ListeRecevables[];
    campagne: string | null;
    compteurs: Record<ListeRecevables, number>;
    lignes: Paginated<LigneClassement> | null;
    colonnes: { age: boolean };
    urls: Partial<Record<ListeRecevables, string | null>>;
    /** Téléchargement de la liste provisoire ouverte (complète) ; null sur la liste des demandes ou sans droit. */
    exports: { excel: string; pdf: string } | null;
};

const ONGLETS: { cle: ListeRecevables; label: string; validee: string }[] = [
    { cle: 'demandes', label: 'Liste des demandes', validee: 'Liste des demandes' },
    { cle: 'admission', label: 'Liste provisoire admissions', validee: 'Admissions' },
    { cle: 'readmission', label: 'Liste provisoire réadmissions', validee: 'Réadmissions' },
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
        <nav ref={navRef} aria-label={classement.validee ? 'Liste validée' : 'Demandes recevables'} className="relative -mb-px ml-auto flex max-w-full flex-wrap items-center gap-1">
            {ONGLETS.filter(({ cle }) => classement.onglets.includes(cle)).map(({ cle, label: libelle, validee }) => {
                const label = classement.validee ? validee : libelle;
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

/** Décision demandée depuis le menu « Plus d'actions » d'une ligne. */
type Decision = { ligne: LigneClassement; action: 'valider' | 'rejeter' };

/**
 * Tableau d'une liste provisoire (admission ou réadmission) ou de la liste validée, dans l'ordre du classement.
 * Chaque ligne se termine par le suivi du dossier, l’état des pièces, puis le menu « Plus d’actions » (validation, rejet) — sur la liste validée, une icône de rejet directe.
 */
export function TableauClassement({ classement, toolbar, filtre }: { classement: Classement; toolbar: ReactNode; filtre: boolean }) {
    const [aDecider, setADecider] = useState<Decision | null>(null);
    const [motif, setMotif] = useState('');
    const [erreur, setErreur] = useState<string | null>(null);
    const [envoi, setEnvoi] = useState(false);

    if (!classement.lignes) return null;
    const readmission = classement.liste === 'readmission';
    // Menu présent sur au moins une ligne : les autres gardent sa place pour aligner les icônes.
    const avecMenu = classement.lignes.data.some((l) => l.decision);

    const ouvrir = (decision: Decision) => {
        setADecider(decision);
        setMotif('');
        setErreur(null);
    };

    const decider = async () => {
        if (!aDecider?.ligne.decision) return;
        const rejet = aDecider.action === 'rejeter';
        // Rejet : motif obligatoire (le serveur le revérifie).
        if (rejet && motif.trim() === '') {
            setErreur('Le motif du rejet est obligatoire.');
            return;
        }
        setEnvoi(true);
        const ok = await envoyer(
            aDecider.ligne.decision.url,
            { etat: rejet ? 'refuse' : 'eligible', ...(rejet ? { motif: motif.trim() } : {}) },
            { onError: (retour) => setErreur(retour.motif ?? Object.values(retour)[0] ?? null) },
        );
        setEnvoi(false);
        if (ok) setADecider(null);
    };

    const vide = (valeur: ReactNode) => valeur ?? <span className="text-muted">—</span>;
    const boursier: DataTableColumn<LigneClassement> = { key: 'boursier', header: 'Boursier', cell: (l) => (l.boursier ? <Badge variant="info">Oui</Badge> : <span className="text-muted">Non</span>) };

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
                  boursier,
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
                  boursier,
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
                      cell: (l) => vide(l.ville_bac),
                  },
              ] satisfies DataTableColumn<LigneClassement>[])),
        ...(classement.colonnes.age
            ? ([{ key: 'age', header: 'Âge', className: 'whitespace-nowrap tabular-nums', cell: (l) => (l.age === null ? vide(null) : `${l.age} ans`) }] satisfies DataTableColumn<LigneClassement>[])
            : []),
        {
            key: 'actions',
            header: <span className="sr-only">Actions</span>,
            className: 'text-right',
            cell: (l) => (
                <div className="flex items-center justify-end gap-1">
                    {l.url && (
                        <Link href={l.url} aria-label={`Suivre le dossier de ${l.name}`} title="Suivre le dossier" className={buttonVariants({ variant: 'ghost', size: 'icon' })}>
                            <Eye {...ICON} aria-hidden />
                        </Link>
                    )}
                    {l.documents && <IconePieces pieces={l.documents} />}
                    {l.decision && classement.validee ? (
                        // Liste validée : rejet seul, icône directe (pas de menu).
                        l.decision.rejeter && (
                            <Button
                                variant="ghost"
                                size="icon"
                                className="text-red-600 hover:text-red-700"
                                aria-label={`Rejeter le dossier de ${l.name}`}
                                title="Rejeter"
                                onClick={() => ouvrir({ ligne: l, action: 'rejeter' })}
                            >
                                <CircleX {...ICON} aria-hidden />
                            </Button>
                        )
                    ) : l.decision ? (
                        // Non modal : la fenêtre de confirmation s'ouvre dès la fermeture du menu.
                        <DropdownMenu modal={false}>
                            <DropdownMenuTrigger asChild>
                                <Button variant="ghost" size="icon" aria-label={`Plus d’actions pour ${l.name}`} title="Plus d’actions">
                                    <EllipsisVertical {...ICON} aria-hidden />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="w-44">
                                {l.decision.valider && (
                                    <DropdownMenuItem className="cursor-pointer" onSelect={() => ouvrir({ ligne: l, action: 'valider' })}>
                                        <CircleCheck aria-hidden className="text-primary" />
                                        Validation
                                    </DropdownMenuItem>
                                )}
                                {l.decision.rejeter && (
                                    <DropdownMenuItem variant="destructive" className="cursor-pointer" onSelect={() => ouvrir({ ligne: l, action: 'rejeter' })}>
                                        <CircleX aria-hidden />
                                        Rejet
                                    </DropdownMenuItem>
                                )}
                            </DropdownMenuContent>
                        </DropdownMenu>
                    ) : (
                        avecMenu && <span className="size-9 shrink-0" aria-hidden />
                    )}
                </div>
            ),
        },
    ];

    const rejet = aDecider?.action === 'rejeter';

    return (
        <>
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
                        : classement.validee
                          ? `Aucune demande ${readmission ? 'de réadmission' : 'd’admission'} validée${classement.campagne ? ` pour la campagne ${classement.campagne}` : ''}.`
                          : `Aucune demande recevable de ${readmission ? 'réadmission' : 'admission'} à classer${classement.campagne ? ` pour la campagne ${classement.campagne}` : ''}.`}
                </span>
            }
        />

        <ConfirmDialog
            open={aDecider !== null}
            onOpenChange={(ouvert) => !ouvert && setADecider(null)}
            title={rejet ? 'Rejeter ce dossier ?' : 'Valider ce dossier ?'}
            description={
                <>
                    {rejet
                        ? `Le dossier de ${aDecider?.ligne.name ?? ''} passe en « Refusé » et rejoint les demandes refusées.`
                        : `Le dossier de ${aDecider?.ligne.name ?? ''} passe en « Recevable » et rejoint la liste validée ; l’étudiant reçoit ses accès et doit confirmer sa demande.`}
                    {!rejet && erreur && (
                        <p role="alert" className="mt-2 text-xs font-medium text-red-600">
                            {erreur}
                        </p>
                    )}
                </>
            }
            confirmLabel={rejet ? 'Rejeter' : 'Valider'}
            confirmIcon={rejet ? <CircleX className="size-4" aria-hidden /> : <CircleCheck className="size-4" aria-hidden />}
            danger={rejet}
            processing={envoi}
            onConfirm={decider}
        >
            {rejet && (
                <Field id="rejet-motif" label="Motif du rejet" required error={erreur ?? undefined} hint="5 000 caractères maximum.">
                    <Textarea
                        id="rejet-motif"
                        rows={3}
                        maxLength={5000}
                        invalid={!!erreur}
                        value={motif}
                        onChange={(event) => {
                            setMotif(event.target.value);
                            setErreur(null);
                        }}
                    />
                </Field>
            )}
        </ConfirmDialog>
        </>
    );
}

/** Pièces du dossier : vert si toutes validées, rouge si au moins une rejetée ou une pièce obligatoire absente, orange en attente de vérification. */
function IconePieces({ pieces }: { pieces: PiecesDossier }) {
    const details = [
        pieces.valides > 0 && `${pieces.valides} validée${pieces.valides > 1 ? 's' : ''}`,
        pieces.rejetes > 0 && `${pieces.rejetes} rejetée${pieces.rejetes > 1 ? 's' : ''}`,
        pieces.absents > 0 && `${pieces.absents} absente${pieces.absents > 1 ? 's' : ''}`,
        pieces.en_verification > 0 && `${pieces.en_verification} en vérification`,
    ].filter(Boolean);
    const { Icone, classe, libelle } = {
        valide: { Icone: FileCheck2, classe: 'text-primary', libelle: 'Pièces validées' },
        incomplet: { Icone: FileX2, classe: 'text-red-600', libelle: 'Pièces rejetées ou absentes' },
        en_verification: { Icone: FileClock, classe: 'text-amber-600', libelle: 'Pièces en vérification' },
    }[pieces.statut];
    const titre = `${libelle} : ${details.join(', ')}`;

    return (
        <span role="img" aria-label={titre} title={titre} className={cn('inline-flex size-9 items-center justify-center', classe)}>
            <Icone {...ICON} aria-hidden />
        </span>
    );
}
