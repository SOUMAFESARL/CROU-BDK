import { ClipboardCheck, Eye, FileDown, LogOut, PenLine, Search, X } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { ConsultationDialog, SignatureEntreeDialog, type Constat, type ResidentEdl } from '@/components/departs/edl';
import { correspond, PaginationLocale, usePaginationLocale, type CiteOption } from '@/components/departs/shared';
import { SortieDialog, type RegleRetenue } from '@/components/departs/sortie-dialog';
import type { DataTableColumn } from '@/components/data-table/data-table';
import { RowAction, RowActions } from '@/components/data-table/row-action';
import { SimpleTable } from '@/components/data-table/simple-table';
import { ICON } from '@/components/layout/app-sidebar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { StatCard, type KpiTone } from '@/components/ui/stat-card';
import { dateFr, nombre } from '@/lib/text';
import { cn } from '@/lib/utils';

/** Données de la liste des états des lieux, partagées par la page Départs et la page États des lieux. */
export type DonneesEtatsDesLieux = {
    residents: ResidentEdl[];
    grille: RegleRetenue[];
    preavisJours: number;
    urls: { store: string | null };
};

type Props = DonneesEtatsDesLieux & {
    cites: CiteOption[];
    /** Étudiant dont l'assistant de sortie doit s'ouvrir (lien « Faire l'état des lieux »). */
    cible?: string | null;
    onCibleOuverte?: () => void;
};

type Statut = '' | 'a_signer' | 'a_realiser' | 'entree' | 'sortie' | 'aucun';

const STATUTS: { value: Exclude<Statut, ''>; label: string }[] = [
    { value: 'a_signer', label: 'Entrée à signer par le CROU' },
    { value: 'a_realiser', label: 'Sortie à réaliser' },
    { value: 'entree', label: 'Avec état d’entrée' },
    { value: 'sortie', label: 'Avec état de sortie' },
    { value: 'aucun', label: 'Sans état des lieux' },
];

const TOUS = 'tous';

const aSigner = (r: ResidentEdl) => !!r.entree?.urls.signer;
const aRealiser = (r: ResidentEdl) => !!r.demande && !r.sortie;

const filtreStatut: Record<Exclude<Statut, ''>, (r: ResidentEdl) => boolean> = {
    a_signer: aSigner,
    a_realiser: aRealiser,
    entree: (r) => !!r.entree,
    sortie: (r) => !!r.sortie,
    aucun: (r) => !r.entree && !r.sortie,
};

/** États des lieux par résident : signature des entrées et sorties à réaliser. */
export function EtatsDesLieuxPanel({ residents, cites, grille, preavisJours, urls, cible, onCibleOuverte }: Props) {
    const [recherche, setRecherche] = useState('');
    const [cite, setCite] = useState('');
    const [statut, setStatut] = useState<Statut>('');
    const [consultation, setConsultation] = useState<{ resident: ResidentEdl; type: 'entree' | 'sortie' } | null>(null);
    const [signature, setSignature] = useState<ResidentEdl | null>(null);
    const [sortie, setSortie] = useState<ResidentEdl | null>(null);
    const filtreActif = Boolean(recherche || cite || statut);

    useEffect(() => {
        if (!cible) return;
        const resident = residents.find((r) => r.id === cible);
        if (resident && aRealiser(resident) && urls.store) setSortie(resident);
        onCibleOuverte?.();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [cible]);

    const filtres = useMemo(
        () =>
            residents.filter(
                (r) =>
                    correspond(recherche, r.name, r.matricule, r.email, r.chambre, r.batiment, r.cite) &&
                    (!cite || r.cite_id === cite) &&
                    (!statut || filtreStatut[statut](r)),
            ),
        [residents, recherche, cite, statut],
    );
    const pages = usePaginationLocale(filtres);

    const kpis: { cle: Statut; label: string; valeur: number; tone: KpiTone; extra?: string; highlight?: boolean }[] = [
        { cle: '', label: 'Résidents suivis', valeur: residents.length, tone: 'blue', extra: `${nombre(residents.filter(filtreStatut.aucun).length)} sans constat` },
        { cle: 'a_signer', label: 'Entrées à signer', valeur: residents.filter(aSigner).length, tone: 'amber', extra: 'Signature du CROU', highlight: residents.some(aSigner) },
        { cle: 'a_realiser', label: 'Sorties à réaliser', valeur: residents.filter(aRealiser).length, tone: 'plum', extra: 'Demandes approuvées', highlight: residents.some(aRealiser) },
        { cle: 'sortie', label: 'Sorties réalisées', valeur: residents.filter(filtreStatut.sortie).length, tone: 'green', extra: 'Séjours clôturés' },
    ];

    const etat = (r: ResidentEdl, type: 'entree' | 'sortie') => {
        const constat: Constat | null = r[type];
        if (!constat) {
            return type === 'sortie' && r.demande ? <Badge variant="warning">À réaliser</Badge> : <Badge variant="neutral">Non établi</Badge>;
        }
        const signeCrou = !!constat.signature_crou;
        const signeEtudiant = !!constat.signature_etudiant;
        return (
            <div className="flex flex-col gap-1">
                <span className="tabular-nums">{dateFr(constat.date)}</span>
                {type === 'entree' ? (
                    <Badge variant={signeCrou ? 'success' : 'warning'}>{signeCrou ? 'Signé par le CROU' : 'À signer par le CROU'}</Badge>
                ) : (
                    <Badge variant={signeEtudiant ? 'success' : 'info'}>{signeEtudiant ? 'Signé par l’étudiant' : 'Signature étudiant attendue'}</Badge>
                )}
            </div>
        );
    };

    const colonnes: DataTableColumn<ResidentEdl>[] = [
        {
            key: 'etudiant',
            header: 'Étudiant',
            cell: (r) => (
                <div className="min-w-40">
                    <p className="font-medium text-gray-900">{r.name}</p>
                    <p className="text-xs text-muted">{[r.matricule, r.email].filter(Boolean).join(' · ') || '—'}</p>
                </div>
            ),
        },
        {
            key: 'logement',
            header: 'Logement',
            cell: (r) => (
                <div className="min-w-32">
                    <p>{r.chambre ? `Chambre ${r.chambre}` : '—'}</p>
                    <p className="text-xs text-muted">{[r.batiment, r.cite].filter(Boolean).join(' · ') || '—'}</p>
                </div>
            ),
        },
        { key: 'entree', header: 'État d’entrée', cell: (r) => etat(r, 'entree') },
        {
            key: 'sortie',
            header: 'État de sortie',
            cell: (r) => (
                <div className="flex flex-col gap-1">
                    {etat(r, 'sortie')}
                    {r.demande && !r.sortie && r.demande.date_sortie && <span className="text-xs text-muted">Sortie prévue le {dateFr(r.demande.date_sortie)}</span>}
                </div>
            ),
        },
        {
            key: 'actions',
            header: <span className="sr-only">Actions</span>,
            className: 'text-right',
            cell: (r) => (
                <RowActions>
                    {r.entree && <RowAction label="Voir l’état d’entrée" icon={Eye} tone="view" onClick={() => setConsultation({ resident: r, type: 'entree' })} />}
                    {aSigner(r) && <RowAction label="Signer l’état d’entrée" icon={PenLine} tone="edit" onClick={() => setSignature(r)} />}
                    {r.sortie && <RowAction label="Voir l’état de sortie" icon={ClipboardCheck} tone="success" onClick={() => setConsultation({ resident: r, type: 'sortie' })} />}
                    {r.sortie?.urls.pdf && <RowAction label="État de sortie en PDF" icon={FileDown} href={r.sortie.urls.pdf} newTab />}
                    {aRealiser(r) && urls.store && <RowAction label="Faire l’état des lieux de sortie" icon={LogOut} tone="danger" onClick={() => setSortie(r)} />}
                </RowActions>
            ),
        },
    ];

    return (
        <>
            <nav aria-label="Filtrer par situation" className="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                {kpis.map((k) => {
                    const actif = k.cle !== '' && statut === k.cle;
                    return (
                        <button
                            key={k.label}
                            type="button"
                            aria-pressed={actif}
                            onClick={() => setStatut(actif ? '' : k.cle)}
                            className={cn(
                                'cursor-pointer rounded-md text-left transition-shadow focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none',
                                actif ? 'ring-2 ring-primary ring-offset-2' : 'hover:shadow-sm',
                            )}
                        >
                            <StatCard tone={k.tone} label={k.label} value={nombre(k.valeur)} extra={k.extra} highlight={k.highlight} />
                        </button>
                    );
                })}
            </nav>

            <section className="mt-6 overflow-hidden rounded-brand border bg-white" aria-label="Résidents et états des lieux">
                <div className="flex flex-col gap-3 border-b p-4 lg:flex-row lg:items-center">
                    <div className="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-3">
                        <Input value={recherche} onChange={(e) => setRecherche(e.target.value)} placeholder="Nom, matricule, chambre…" aria-label="Rechercher un résident" icon={<Search />} className="h-10" />
                        <Select value={cite || TOUS} onValueChange={(v) => setCite(v === TOUS ? '' : v)}>
                            <SelectTrigger className="h-10" aria-label="Filtrer par cité">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={TOUS}>Toutes les cités</SelectItem>
                                {cites.map((c) => (
                                    <SelectItem key={c.id} value={c.id}>
                                        {c.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Select value={statut || TOUS} onValueChange={(v) => setStatut(v === TOUS ? '' : (v as Statut))}>
                            <SelectTrigger className="h-10" aria-label="Filtrer par situation">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={TOUS}>Toutes les situations</SelectItem>
                                {STATUTS.map((s) => (
                                    <SelectItem key={s.value} value={s.value}>
                                        {s.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    {filtreActif && (
                        <Button
                            variant="ghost"
                            size="sm"
                            className="h-10"
                            onClick={() => {
                                setRecherche('');
                                setCite('');
                                setStatut('');
                            }}
                        >
                            <X className="size-4" aria-hidden /> Réinitialiser
                        </Button>
                    )}
                </div>
                <SimpleTable
                    columns={colonnes}
                    rows={pages.visibles}
                    rowKey={(r) => r.id}
                    emptyMessage={
                        <span className="flex flex-col items-center gap-2">
                            <ClipboardCheck {...ICON} aria-hidden />
                            {filtreActif ? 'Aucun résident ne correspond à ces filtres.' : 'Aucun état des lieux ni demande de sortie approuvée.'}
                        </span>
                    }
                />
                <PaginationLocale {...pages} />
            </section>

            <ConsultationDialog cible={consultation} onClose={() => setConsultation(null)} />
            <SignatureEntreeDialog resident={signature} onClose={() => setSignature(null)} />
            {urls.store && <SortieDialog resident={sortie} url={urls.store} grille={grille} preavisJours={preavisJours} onClose={() => setSortie(null)} />}
        </>
    );
}
