import { router } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, Loader2, Save, Search } from 'lucide-react';
import { useEffect, useRef, useState, type ReactNode } from 'react';
import type { FieldValues, Path, UseFormTrigger } from 'react-hook-form';
import { STATUTS_CAMPAGNE, type StatutCampagne } from '@/components/campagnes/types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DialogFooter } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

/** Campagne proposée dans la liste de choix des pages de campagne (`ParametrageHebergement::options()`). */
export type CampagneOption = { id: string; annee: string | null; description: string | null; statut: string | null };

/** Totaux d'un ensemble de chambres (`ParametrageHebergement::capacite()`). */
export type Capacite = { chambres: number; places: number; occupees: number; libres: number; pmr: number };

/** Part des places occupées, en %. */
export const taux = (c: Capacite) => (c.places > 0 ? Math.round((c.occupees / c.places) * 100) : 0);

/**
 * Recherche instantanée côté serveur (`?q=`, 300 ms après la dernière frappe) qui conserve les autres filtres.
 * `only` limite le rechargement aux props de la liste.
 */
export function useRecherche(url: string, initiale: string, autres: Record<string, string> = {}, only?: string[]) {
    const [recherche, setRecherche] = useState(initiale);
    const premierRendu = useRef(true);

    useEffect(() => {
        if (premierRendu.current) {
            premierRendu.current = false;
            return;
        }
        const minuteur = setTimeout(() => {
            const parametres = Object.fromEntries(Object.entries({ ...autres, q: recherche.trim() }).filter(([, v]) => v !== '' && v !== 'tous'));
            router.get(url, parametres, { preserveState: true, preserveScroll: true, replace: true, only });
        }, 300);
        return () => clearTimeout(minuteur);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [recherche]);

    return [recherche, setRecherche] as const;
}

export function ChampRecherche({ value, onChange, placeholder }: { value: string; onChange: (valeur: string) => void; placeholder: string }) {
    return (
        <div className="w-full sm:max-w-xs">
            <Input
                type="search"
                value={value}
                onChange={(event) => onChange(event.target.value)}
                placeholder={placeholder}
                aria-label={placeholder}
                icon={<Search />}
            />
        </div>
    );
}

/**
 * Navigation d'un assistant en plusieurs étapes : avancer (bouton ou clic sur une étape) valide chaque étape intermédiaire,
 * on s'arrête sur la première étape incomplète.
 */
export function useAssistant<T extends FieldValues>(champsEtapes: readonly (readonly Path<T>[])[], trigger: UseFormTrigger<T>, toutesAccessibles = false) {
    const [etape, setEtape] = useState(0);
    const [atteinte, setAtteinte] = useState(toutesAccessibles ? champsEtapes.length - 1 : 0);

    const aller = (index: number) => {
        setEtape(index);
        setAtteinte((max) => Math.max(max, index));
    };

    const allerA = async (cible: number) => {
        if (cible <= etape) {
            setEtape(cible);
            return true;
        }
        for (let index = etape; index < cible; index++) {
            if (!(await trigger(champsEtapes[index] as Path<T>[], { shouldFocus: true }))) {
                aller(index);
                return false;
            }
        }
        aller(cible);
        return true;
    };

    /** Revérifie toutes les étapes avant l'envoi. */
    const toutValider = async () => {
        for (let index = 0; index < champsEtapes.length; index++) {
            if (!(await trigger(champsEtapes[index] as Path<T>[], { shouldFocus: true }))) {
                aller(index);
                return false;
            }
        }
        return true;
    };

    return { etape, atteinte, aller, allerA, toutValider, derniere: etape === champsEtapes.length - 1 };
}

/** Pied des assistants dans une fenêtre : Annuler à gauche, Précédent / Suivant ou Enregistrer à droite. */
export function PiedAssistant({
    etape,
    derniere,
    envoi,
    onAnnuler,
    onPrecedent,
    libelle = 'Enregistrer',
    desactive = false,
}: {
    etape: number;
    derniere: boolean;
    envoi: boolean;
    onAnnuler: () => void;
    onPrecedent: () => void;
    libelle?: string;
    desactive?: boolean;
}) {
    return (
        <DialogFooter className="sm:justify-between">
            <Button variant="outline" size="sm" className="h-10" onClick={onAnnuler} disabled={envoi}>
                Annuler
            </Button>
            <div className="flex flex-col-reverse gap-2 sm:flex-row">
                {etape > 0 && (
                    <Button variant="outline" size="sm" className="h-10" onClick={onPrecedent} disabled={envoi}>
                        <ArrowLeft className="size-4" aria-hidden /> Précédent
                    </Button>
                )}
                {derniere ? (
                    <Button type="submit" size="sm" className="h-10" disabled={envoi || desactive}>
                        {envoi ? <Loader2 className="size-4 animate-spin" aria-hidden /> : <Save className="size-4" aria-hidden />}
                        {libelle}
                    </Button>
                ) : (
                    <Button type="submit" size="sm" className="h-10">
                        Suivant <ArrowRight className="size-4" aria-hidden />
                    </Button>
                )}
            </div>
        </DialogFooter>
    );
}

/** Récapitulatif de la dernière étape. */
export function Recap({ lignes }: { lignes: { label: string; valeur: ReactNode }[] }) {
    return (
        <dl className="divide-y rounded-brand border text-sm">
            {lignes.map((ligne) => (
                <div key={ligne.label} className="flex flex-col gap-0.5 px-4 py-2.5 sm:flex-row sm:items-start sm:justify-between sm:gap-6">
                    <dt className="shrink-0 text-muted">{ligne.label}</dt>
                    <dd className="min-w-0 text-gray-900 sm:text-right">{ligne.valeur}</dd>
                </div>
            ))}
        </dl>
    );
}

export function ErreurGenerale({ message }: { message: string | null }) {
    if (!message) return null;
    return (
        <p role="alert" className="rounded-brand border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {message}
        </p>
    );
}

export const libelleCampagne = (c: CampagneOption) => [c.annee, c.description].filter(Boolean).join(' — ') || 'Campagne sans titre';

/** Choix de la campagne affichée (`?campagne_id=`), commun aux pages de campagne du paramétrage. */
export function ChoixCampagne({ campagnes, valeur, url }: { campagnes: CampagneOption[]; valeur: string | null; url: string }) {
    const choisie = campagnes.find((c) => c.id === valeur);
    const statut = choisie?.statut && choisie.statut in STATUTS_CAMPAGNE ? STATUTS_CAMPAGNE[choisie.statut as StatutCampagne] : null;

    return (
        <div className="mb-4 flex flex-col gap-3 rounded-brand border bg-white p-4 sm:flex-row sm:items-center">
            <label htmlFor="choix-campagne" className="shrink-0 text-sm font-medium text-gray-800">
                Campagne
            </label>
            <div className="w-full sm:max-w-md">
                <Select
                    value={valeur ?? undefined}
                    onValueChange={(id) => router.get(url, { campagne_id: id }, { preserveScroll: true, replace: true })}
                >
                    <SelectTrigger id="choix-campagne">
                        <SelectValue placeholder="Choisir une campagne" />
                    </SelectTrigger>
                    <SelectContent>
                        {campagnes.map((c) => (
                            <SelectItem key={c.id} value={c.id}>
                                {libelleCampagne(c)}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
            {statut && <Badge variant={statut.variant}>{statut.label}</Badge>}
        </div>
    );
}

/** Carte de contenu avec un titre et une icône (pages de campagne). */
export function Bloc({ titre, icone, children, actions }: { titre: ReactNode; icone: ReactNode; children: ReactNode; actions?: ReactNode }) {
    return (
        <section className="h-fit overflow-hidden rounded-brand border bg-white">
            <h2 className="flex items-center gap-2 border-b px-4 py-3 text-sm font-medium text-gray-900">
                <span className="text-primary" aria-hidden>
                    {icone}
                </span>
                {titre}
                {actions && <span className="ml-auto">{actions}</span>}
            </h2>
            {children}
        </section>
    );
}

/** Aucun élément : message centré sur fond blanc. */
export function Vide({ icone, children }: { icone: ReactNode; children: ReactNode }) {
    return (
        <div className="flex flex-col items-center gap-2 rounded-brand border border-dashed bg-white px-6 py-14 text-center text-sm text-muted">
            <span className="text-gray-400 [&_svg]:size-6" aria-hidden>
                {icone}
            </span>
            {children}
        </div>
    );
}

/** Encadré d'information (règle métier rappelée à l'administrateur). */
export function Note({ icone, children, ton = 'info' }: { icone: ReactNode; children: ReactNode; ton?: 'info' | 'attention' }) {
    return (
        <div
            className={
                ton === 'attention'
                    ? 'flex items-start gap-2.5 rounded-brand border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900'
                    : 'flex items-start gap-2.5 rounded-brand border border-[#C6E3A6] bg-soft px-4 py-3 text-sm text-gray-800'
            }
        >
            <span className="mt-px shrink-0 [&_svg]:size-[18px] [&_svg]:stroke-[1.25]" aria-hidden>
                {icone}
            </span>
            <div className="min-w-0 space-y-1">{children}</div>
        </div>
    );
}
