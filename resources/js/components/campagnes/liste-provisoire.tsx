import { Link } from '@inertiajs/react';
import { CircleCheck, CirclePause, CircleX, EllipsisVertical, Eye, FileCheck2, FileClock, FileX2, Loader, ListOrdered, Scale } from 'lucide-react';
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
import { dateFr, initiales, nombre } from '@/lib/text';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types/global';

/**
 * Onglets des demandes recevables : la liste des demandes, puis les listes provisoires par volet ; onglets de la
 * décision de la commission et des listes définitives : les demandes validées, puis la file d'attente ; `toutes` : liste
 * unique (sans onglets).
 */
export type ListeRecevables = 'demandes' | 'admission' | 'readmission' | 'validees' | 'attente' | 'toutes';

export type Volet = 'admission' | 'readmission';

export type LigneClassement = {
    id: string;
    /** Volet de la demande : le rang est propre à chaque volet. */
    volet: Volet;
    rang: number;
    name: string;
    numero_demande: string | null;
    matricule: string | null;
    civilite: string | null;
    /** `aaaa-mm-jj` ; envoyée seulement avec le droit de voir les données personnelles. */
    date_naissance: string | null;
    filiere: string | null;
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
    /** Demande rendue recevable par dérogation (bouton « Déroger » de la fiche) : en tête de la liste provisoire. */
    derogee: boolean;
    /** Décision de la commission : demande validée ou en attente (date de la mise en attente) ; null ailleurs. */
    commission: { decision: DecisionCommission; date: string | null } | null;
    /**
     * Décisions de la commission depuis la liste provisoire : validation (`admin.dossiers.transition`, dossier
     * « Recevable »), mise en attente (`attente` : URL, l'état du dossier ne change pas) — la demande passe dans la
     * « Décision de la commission » — et rejet (« Refusé », motif obligatoire, la demande passe dans « Demandes
     * refusées »). Sur la décision de la commission, une demande en attente peut être validée ou rejetée, une demande
     * validée mise en attente (son dossier revient en « Soumis ») ou rejetée ; aucune action sur les listes définitives.
     * Null sans le droit de changer l'état du dossier ou si aucune n'est permise.
     */
    decision: { url: string; valider: boolean; rejeter: boolean; attente: string | null } | null;
};

/** Décision de la commission sur une demande d'une liste provisoire (hors rejet). */
export type DecisionCommission = 'validee' | 'attente';

export type PiecesDossier = {
    statut: 'valide' | 'incomplet' | 'en_verification';
    valides: number;
    rejetes: number;
    en_verification: number;
    absents: number;
};

/**
 * `provisoire` : listes provisoires des demandes recevables ; `validee` : « Décision de la commission » (onglets
 * « Demandes validées » et « File d'attente ») ; `definitive` : « Listes définitives » (demandes approuvées en commission,
 * mêmes onglets selon la liste d'origine de l'approbation). Hors listes provisoires, admissions et réadmissions sont
 * mêlées, filtrables par type de demande.
 */
export type TypeClassement = 'provisoire' | 'validee' | 'definitive';

export type Classement = {
    liste: ListeRecevables;
    type: TypeClassement;
    /**
     * Onglets affichés : la liste des demandes, puis une liste provisoire par volet ouvert par la campagne ; sur la
     * décision de la commission et les listes définitives, les demandes validées puis la file d'attente.
     */
    onglets: ListeRecevables[];
    /** Décision de la commission / listes définitives : type de demande filtré (null : admissions et réadmissions). */
    volet: Volet | null;
    /** Décision de la commission / listes définitives : onglet ouvert ; null ailleurs. */
    decision: DecisionCommission | null;
    campagne: string | null;
    /** `validees` / `attente` : onglets de la décision de la commission et des listes définitives (type de demande filtré). */
    compteurs: Record<ListeRecevables, number>;
    /**
     * Décision de la commission : demandes déjà approuvées pour la campagne depuis chaque onglet (type de demande
     * filtré) ; un onglet vide dont la liste est approuvée l'indique. Null ailleurs.
     */
    approuvees: { validees: number; attente: number } | null;
    lignes: Paginated<LigneClassement> | null;
    colonnes: { age: boolean };
    urls: Partial<Record<ListeRecevables, string | null>>;
    /**
     * Téléchargement de la liste provisoire ou définitive ouverte (complète) ; null sur la liste des demandes, la décision
     * de la commission, une liste définitive pas encore publiée ou sans droit.
     */
    exports: { excel: string; pdf: string } | null;
    /**
     * Listes définitives : publication de l'onglet ouvert (type de demande filtré), `date` : date de publication
     * (Y-m-d H:i). `url` : bouton « Publier » des deux onglets, sur « Demandes validées » seulement (null une fois tout
     * publié ou sans droit) ; `validees` / `attente` : demandes restant à publier dans chaque onglet. Null ailleurs.
     */
    publication: { publiee: boolean; date: string | null; url: string | null; validees: number; attente: number } | null;
    /**
     * « Approuver » les demandes validées et la file d'attente, depuis l'un ou l'autre onglet de la décision de
     * la commission (même action) : elles passent dans l'onglet correspondant des listes définitives — celles de la file d'attente
     * sont d'abord validées (dossier « Recevable ») ; null ailleurs ou sans droit.
     */
    approbation: { url: string; validees: number; attente: number } | null;
    /**
     * « Tout valider » la liste provisoire des admissions : ses `nombre` demandes sont partagées entre les demandes
     * validées (places libres) et la file d'attente (`validees` / `attente` null : places non renseignées). Null ailleurs ou sans droit.
     */
    validationGroupee: { url: string; nombre: number; validees: number | null; attente: number | null } | null;
};

const ONGLETS: { cle: Exclude<ListeRecevables, 'toutes'>; label: string }[] = [
    { cle: 'demandes', label: 'Liste des demandes' },
    { cle: 'admission', label: 'Liste provisoire admissions' },
    { cle: 'readmission', label: 'Liste provisoire réadmissions' },
    { cle: 'validees', label: 'Demandes validées' },
    { cle: 'attente', label: 'File d’attente' },
];

const NOMS_LISTE: Record<TypeClassement, string> = {
    provisoire: 'Demandes recevables',
    validee: 'Décision de la commission',
    definitive: 'Listes définitives',
};

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
        <nav ref={navRef} aria-label={NOMS_LISTE[classement.type]} className="relative -mb-px ml-auto flex max-w-full flex-wrap items-center gap-1">
            {ONGLETS.filter(({ cle }) => classement.onglets.includes(cle)).map(({ cle, label }) => {
                const courant = cle === actif;
                const url = classement.urls[cle];
                const classes = cn(
                    'inline-flex h-10 shrink-0 items-center gap-2 px-3 text-sm whitespace-nowrap transition-colors duration-300',
                    'focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none focus-visible:ring-inset',
                    courant ? 'font-medium text-primary' : 'text-muted hover:text-primary',
                );
                // Liste en attente d'approbation (réadmissions, décision de la commission) : icône « en cours », sans
                // compteur ; la liste des demandes, la liste provisoire des admissions et les listes définitives
                // (approuvées) gardent leur compteur.
                const enAttente = classement.type !== 'definitive' && cle !== 'demandes' && cle !== 'admission';
                const contenu = enAttente ? (
                    <>
                        <Loader {...ICON} aria-hidden />
                        {label}
                    </>
                ) : (
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
type Decision = { ligne: LigneClassement; action: 'valider' | 'attente' | 'rejeter' };

/**
 * Tableau d'une liste provisoire (admission ou réadmission), de la décision de la commission ou d'une liste définitive,
 * dans l'ordre du classement. Chaque ligne se termine par le suivi du dossier, l’état des pièces, puis le menu « Plus
 * d’actions » : valider, mettre en attente, rejeter sur les listes provisoires ; mettre en attente, rejeter sur les
 * demandes validées ; valider, rejeter dans la file d'attente ; aucune action sur les listes définitives.
 */
export function TableauClassement({ classement, toolbar, filtre }: { classement: Classement; toolbar: ReactNode; filtre: boolean }) {
    const [aDecider, setADecider] = useState<Decision | null>(null);
    const [motif, setMotif] = useState('');
    const [erreur, setErreur] = useState<string | null>(null);
    const [envoi, setEnvoi] = useState(false);

    if (!classement.lignes) return null;
    // Volet affiché : celui de l'onglet de liste provisoire, sinon le type de demande filtré ; null : admissions et
    // réadmissions mêlées.
    const volet: Volet | null = classement.type !== 'provisoire' ? classement.volet : classement.liste === 'readmission' ? 'readmission' : 'admission';
    const readmission = volet === 'readmission';
    // Menu présent sur au moins une ligne : les autres gardent sa place pour aligner les icônes.
    const avecMenu = classement.lignes.data.some((l) => l.decision);

    const ouvrir = (decision: Decision) => {
        setADecider(decision);
        setMotif('');
        setErreur(null);
    };

    const decider = async () => {
        const decision = aDecider?.ligne.decision;
        if (!aDecider || !decision) return;
        const rejet = aDecider.action === 'rejeter';
        // Rejet : motif obligatoire (le serveur le revérifie).
        if (rejet && motif.trim() === '') {
            setErreur('Le motif du rejet est obligatoire.');
            return;
        }
        setEnvoi(true);
        const ok =
            aDecider.action === 'attente'
                ? // Mise en attente : sans motif, l'état du dossier ne change pas.
                  decision.attente !== null &&
                  (await envoyer(decision.attente, {}, { onError: (retour) => setErreur(retour.attente ?? Object.values(retour)[0] ?? null) }))
                : await envoyer(
                      decision.url,
                      { etat: rejet ? 'refuse' : 'eligible', ...(rejet ? { motif: motif.trim() } : {}) },
                      { onError: (retour) => setErreur(retour.motif ?? Object.values(retour)[0] ?? null) },
                  );
        setEnvoi(false);
        if (ok) setADecider(null);
    };

    const vide = (valeur: ReactNode) => valeur ?? <span className="text-muted">—</span>;
    const derogee = (l: LigneClassement) =>
        l.derogee && (
            <Badge variant="warning" title="Demande rendue recevable par dérogation">
                <Scale className="size-3.5" aria-hidden /> Dérogé
            </Badge>
        );
    const boursier: DataTableColumn<LigneClassement> = { key: 'boursier', header: 'Boursier', cell: (l) => (l.boursier ? <Badge variant="info">Oui</Badge> : <span className="text-muted">Non</span>) };
    // Réadmission : catégorie, puis moyenne et crédits ; admission : note du bac ou crédits, puis ville du bac.
    const categorie: DataTableColumn<LigneClassement> = {
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
    };
    const merite: DataTableColumn<LigneClassement> = {
        key: 'merite',
        header: 'Mérite',
        className: 'whitespace-nowrap tabular-nums',
        cell: (l) =>
            l.volet === 'readmission' ? (
                <>
                    {l.moyenne === null ? vide(null) : `${nombre(l.moyenne, 2)}/20`}
                    {l.credits !== null && <p className="text-xs text-muted">{nombre(l.credits, 2)} % des crédits</p>}
                </>
            ) : l.merite ? (
                <>
                    {nombre(l.merite.valeur, 2)}/{l.merite.max}
                    <p className="text-xs text-muted">
                        {l.merite.libelle} · {nombre(l.merite.pourcentage, 2)} %
                    </p>
                </>
            ) : (
                vide(null)
            ),
    };
    const villeBac: DataTableColumn<LigneClassement> = { key: 'ville_bac', header: 'Ville du bac', cell: (l) => vide(l.ville_bac) };
    const rang: DataTableColumn<LigneClassement> = { key: 'rang', header: 'Rang', headClassName: 'w-16', className: 'font-medium text-gray-900 tabular-nums', cell: (l) => l.rang };
    const niveau: DataTableColumn<LigneClassement> = { key: 'niveau', header: 'Niveau', cell: (l) => vide(l.niveau) };
    const actions: DataTableColumn<LigneClassement> = {
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
                {l.decision ? (
                    // Non modal : la fenêtre de confirmation s'ouvre dès la fermeture du menu.
                    <DropdownMenu modal={false}>
                        <DropdownMenuTrigger asChild>
                            <Button variant="ghost" size="icon" aria-label={`Plus d’actions pour ${l.name}`} title="Plus d’actions">
                                <EllipsisVertical {...ICON} aria-hidden />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" className="w-48">
                            {l.decision.valider && (
                                <DropdownMenuItem className="cursor-pointer" onSelect={() => ouvrir({ ligne: l, action: 'valider' })}>
                                    <CircleCheck aria-hidden className="text-primary" />
                                    Valider
                                </DropdownMenuItem>
                            )}
                            {l.decision.attente && (
                                <DropdownMenuItem className="cursor-pointer" onSelect={() => ouvrir({ ligne: l, action: 'attente' })}>
                                    <CirclePause aria-hidden className="text-amber-600" />
                                    Mettre en attente
                                </DropdownMenuItem>
                            )}
                            {l.decision.rejeter && (
                                <DropdownMenuItem variant="destructive" className="cursor-pointer" onSelect={() => ouvrir({ ligne: l, action: 'rejeter' })}>
                                    <CircleX aria-hidden />
                                    Rejeter
                                </DropdownMenuItem>
                            )}
                        </DropdownMenuContent>
                    </DropdownMenu>
                ) : (
                    avecMenu && <span className="size-9 shrink-0" aria-hidden />
                )}
            </div>
        ),
    };

    // Onglet « Demandes validées » de la décision de la commission et des listes définitives : rang, matricule, civilité,
    // nom et prénoms, date de naissance (avec le droit de voir les données personnelles), filière, niveau (puis le suivi
    // du dossier, l'état des pièces et le menu d'actions).
    const demandesValidees = classement.type !== 'provisoire' && classement.liste === 'validees';

    const colonnes: DataTableColumn<LigneClassement>[] = demandesValidees
        ? [
              rang,
              { key: 'matricule', header: 'Matricule', className: 'whitespace-nowrap tabular-nums', cell: (l) => vide(l.matricule) },
              { key: 'civilite', header: 'Civilité', cell: (l) => vide(l.civilite) },
              {
                  key: 'nom',
                  header: 'Nom & prénoms',
                  cell: (l) => (
                      <span className="inline-flex flex-wrap items-center gap-2">
                          <span className="font-medium text-gray-900">{l.name}</span>
                          {derogee(l)}
                      </span>
                  ),
              },
              ...(classement.colonnes.age
                  ? ([
                        { key: 'date_naissance', header: 'Date de naissance', className: 'whitespace-nowrap tabular-nums', cell: (l) => (l.date_naissance ? dateFr(l.date_naissance) : vide(null)) },
                    ] satisfies DataTableColumn<LigneClassement>[])
                  : []),
              { key: 'filiere', header: 'Filière', cell: (l) => vide(l.filiere) },
              niveau,
              actions,
          ]
        : [
              rang,
              {
                  key: 'etudiant',
                  header: 'Étudiant',
                  cell: (l) => (
                      <div className="flex min-w-48 items-center gap-3">
                          <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-soft text-xs font-medium text-primary" aria-hidden>
                              {initiales(l.name)}
                          </span>
                          <div className="min-w-0">
                              <p className="flex flex-wrap items-center gap-2 font-medium text-gray-900">
                                  {l.name}
                                  {derogee(l)}
                              </p>
                              {l.numero_demande && <p className="text-xs text-muted">#{l.numero_demande}</p>}
                          </div>
                      </div>
                  ),
              },
              // Admissions et réadmissions mêlées : type de la demande (le rang est propre à chaque volet).
              ...(volet === null
                  ? ([
                        {
                            key: 'type',
                            header: 'Type de demande',
                            cell: (l) => <Badge variant={l.volet === 'readmission' ? 'info' : 'success'}>{l.volet === 'readmission' ? 'Réadmission' : 'Admission'}</Badge>,
                        },
                    ] satisfies DataTableColumn<LigneClassement>[])
                  : []),
              niveau,
              // Pas de colonne « Catégorie » sur la décision de la commission.
              ...(readmission
                  ? ([...(classement.type === 'validee' ? [] : [categorie]), boursier, { ...merite, header: 'Moyenne' }] satisfies DataTableColumn<LigneClassement>[])
                  : // Admission, ou liste mêlée : chaque ligne affiche le mérite de son volet (« — » pour la ville du bac d'une réadmission).
                    ([boursier, merite, villeBac] satisfies DataTableColumn<LigneClassement>[])),
              ...(classement.colonnes.age
                  ? ([{ key: 'age', header: 'Âge', className: 'whitespace-nowrap tabular-nums', cell: (l) => (l.age === null ? vide(null) : `${l.age} ans`) }] satisfies DataTableColumn<LigneClassement>[])
                  : []),
              actions,
          ];

    const rejet = aDecider?.action === 'rejeter';
    const attente = aDecider?.action === 'attente';
    const nom = aDecider?.ligne.name ?? '';
    const dialogue = rejet
        ? {
              titre: 'Rejeter ce dossier ?',
              texte: `Le dossier de ${nom} passe en « Refusé » et rejoint les demandes refusées.`,
              bouton: 'Rejeter',
              icone: <CircleX className="size-4" aria-hidden />,
          }
        : attente
          ? {
                titre: 'Mettre ce dossier en attente ?',
                texte:
                    aDecider?.ligne.commission?.decision === 'validee'
                        ? `La validation du dossier de ${nom} est retirée : il revient en « Soumis » et la demande passe dans la file d’attente, où elle est classée avec les autres demandes en attente. Elle pourra y être validée ou rejetée.`
                        : `La demande de ${nom} passe dans la file d’attente de la décision de la commission, où elle est classée avec les autres demandes en attente ; l’état du dossier ne change pas. Elle pourra y être validée ou rejetée.`,
                bouton: 'Mettre en attente',
                icone: <CirclePause className="size-4" aria-hidden />,
            }
          : {
                titre: 'Valider ce dossier ?',
                texte: `Le dossier de ${nom} passe en « Recevable » et rejoint les demandes validées de la décision de la commission ; l’étudiant reçoit ses accès et doit confirmer sa demande.`,
                bouton: 'Valider',
                icone: <CircleCheck className="size-4" aria-hidden />,
            };

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
                        : classement.type === 'definitive'
                          ? `Aucune demande ${volet === null ? '' : readmission ? 'de réadmission ' : 'd’admission '}${
                                classement.liste === 'attente' ? 'de la file d’attente' : 'validée'
                            } approuvée en commission${classement.campagne ? ` pour la campagne ${classement.campagne}` : ''}.`
                          : classement.type === 'validee' && (classement.approuvees?.[classement.liste === 'attente' ? 'attente' : 'validees'] ?? 0) > 0
                            ? // Onglet vide après approbation : la liste est déjà approuvée pour la campagne.
                              `${classement.liste === 'attente' ? 'La file d’attente' : 'La liste des demandes validées'}${
                                  volet === null ? '' : readmission ? ' de réadmission' : ' d’admission'
                              }${classement.campagne ? ` de la campagne ${classement.campagne}` : ''} est déjà approuvée : ses demandes figurent dans les listes définitives.`
                          : classement.type === 'validee'
                          ? `Aucune demande ${volet === null ? '' : readmission ? 'de réadmission ' : 'd’admission '}${
                                classement.liste === 'attente' ? 'en attente' : 'validée'
                            }${classement.campagne ? ` pour la campagne ${classement.campagne}` : ''}.`
                          : `Aucune demande recevable de ${readmission ? 'réadmission' : 'admission'} à classer${classement.campagne ? ` pour la campagne ${classement.campagne}` : ''}.`}
                </span>
            }
        />

        <ConfirmDialog
            open={aDecider !== null}
            onOpenChange={(ouvert) => !ouvert && setADecider(null)}
            title={dialogue.titre}
            description={
                <>
                    {dialogue.texte}
                    {!rejet && erreur && (
                        <p role="alert" className="mt-2 text-xs font-medium text-red-600">
                            {erreur}
                        </p>
                    )}
                </>
            }
            confirmLabel={dialogue.bouton}
            confirmIcon={dialogue.icone}
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
