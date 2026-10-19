import { zodResolver } from '@hookform/resolvers/zod';
import { FileDown, Loader2, PenLine, PenTool, TrendingDown } from 'lucide-react';
import { useEffect } from 'react';
import { Controller, useForm } from 'react-hook-form';
import { VignettesEdl } from '@/components/etudiant/photos-edl';
import { SignaturePad } from '@/components/etudiant/signature-pad';
import { ICON } from '@/components/layout/app-sidebar';
import { Badge } from '@/components/ui/badge';
import { Button, buttonVariants } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Field } from '@/components/ui/field';
import { Lignes } from '@/components/vie-residentielle/shared';
import { envoyer } from '@/lib/inertia-form';
import { dateFr } from '@/lib/text';
import { cn } from '@/lib/utils';
import { signatureEntreeSchema, type SignatureEntree } from '@/schemas/departs';
import { ETATS, type Etat } from '@/schemas/etat-des-lieux';

export type Photo = { chemin: string; url: string | null };
export type ElementConstat = { nom: string; etat: Etat | null; observation: string | null; photo: Photo | null };
export type PieceConstat = { nom: string; photos: Photo[]; elements: ElementConstat[] };

export type Constat = {
    id: string;
    chambre_id: string | null;
    date: string | null;
    cles: number | null;
    observation: string | null;
    signature_crou: string | null;
    signature_etudiant: string | null;
    pieces: PieceConstat[];
    urls: { signer: string | null; pdf: string | null };
};

export type ResidentEdl = {
    id: string;
    name: string;
    email: string | null;
    matricule: string | null;
    cite: string | null;
    cite_id: string | null;
    batiment: string | null;
    batiment_id: string | null;
    chambre: string | null;
    chambre_id: string | null;
    entree: Constat | null;
    sortie: Constat | null;
    /** Demande de sortie approuvée, en attente de l'état des lieux. */
    demande: { id: string; date_demande: string | null; date_sortie: string | null } | null;
};

export const RANG: Record<Etat, number> = { bon: 0, moyen: 1, mauvais: 2 };

export const memeNom = (a: string, b: string) => a.trim().toLowerCase() === b.trim().toLowerCase();

/** Élément correspondant de l'état d'entrée : même pièce et même nom, à défaut même position. */
export function correspondant(reference: Constat | null | undefined, pieceIndex: number, pieceNom: string, elementIndex: number, elementNom: string) {
    if (!reference) return undefined;
    const piece = reference.pieces.find((p) => memeNom(p.nom, pieceNom)) ?? reference.pieces[pieceIndex];
    return piece?.elements.find((e) => memeNom(e.nom, elementNom)) ?? piece?.elements[elementIndex];
}

export function BadgeEtat({ etat }: { etat: Etat | null }) {
    const option = ETATS.find((e) => e.code === etat);
    return <Badge variant={option?.ton ?? 'neutral'}>{option?.libelle ?? 'Non renseigné'}</Badge>;
}

export const logementDe = (r: ResidentEdl) => [r.chambre && `Chambre ${r.chambre}`, r.batiment, r.cite].filter(Boolean).join(' · ') || '—';

function CadreSignature({ libelle, image, attente }: { libelle: string; image: string | null; attente: string }) {
    return (
        <div className="rounded-brand border p-3">
            <p className="mb-2 flex items-center gap-1.5 text-xs text-muted">
                <PenTool {...ICON} size={14} aria-hidden />
                {libelle}
            </p>
            {image ? (
                <img src={image} alt={`Signature : ${libelle}`} className="h-16 w-full object-contain" />
            ) : (
                <p className="grid h-16 place-items-center rounded-brand bg-surface text-xs text-muted">{attente}</p>
            )}
        </div>
    );
}

/** Constat en lecture seule ; pour une sortie, chaque élément est comparé à l'état d'entrée. */
export function DetailConstat({ resident, constat, reference, signatures = true }: { resident: ResidentEdl; constat: Constat; reference?: Constat | null; signatures?: boolean }) {
    return (
        <div className="space-y-4">
            <Lignes
                lignes={[
                    ['Étudiant', `${resident.name}${resident.matricule ? ` (${resident.matricule})` : ''}`],
                    ['Logement', logementDe(resident)],
                    ['Date du constat', dateFr(constat.date)],
                    ['Clés', constat.cles ?? '—'],
                ]}
            />

            {constat.pieces.length === 0 ? (
                <p className="rounded-brand border border-dashed px-4 py-6 text-center text-sm text-muted">Aucune pièce n’a été détaillée dans ce constat.</p>
            ) : (
                constat.pieces.map((piece, i) => (
                    <section key={`${piece.nom}-${i}`} className="rounded-brand border" aria-label={piece.nom || `Pièce ${i + 1}`}>
                        <h3 className="flex items-center justify-between gap-2 border-b bg-surface px-3 py-2.5 text-sm font-medium text-gray-900">
                            {piece.nom || `Pièce ${i + 1}`}
                            <span className="text-xs font-normal text-muted">
                                {piece.elements.length} élément{piece.elements.length > 1 ? 's' : ''}
                            </span>
                        </h3>
                        <ul className="divide-y">
                            {piece.elements.map((element, j) => {
                                const ref = reference !== undefined ? correspondant(reference, i, piece.nom, j, element.nom) : undefined;
                                const degrade = !!(ref?.etat && element.etat && RANG[element.etat] > RANG[ref.etat]);
                                return (
                                    <li key={`${element.nom}-${j}`} className="flex items-start gap-3 px-3 py-2.5">
                                        <div className="min-w-0 flex-1">
                                            <p className="text-sm text-gray-900">{element.nom || `Élément ${j + 1}`}</p>
                                            {element.observation && <p className="mt-0.5 text-xs text-muted">{element.observation}</p>}
                                            {reference !== undefined && (
                                                <p className={cn('mt-1 inline-flex items-center gap-1 text-xs', degrade ? 'font-medium text-red-700' : 'text-muted')}>
                                                    {degrade && <TrendingDown {...ICON} size={14} aria-hidden />}
                                                    À l’entrée : {ETATS.find((e) => e.code === ref?.etat)?.libelle ?? 'non renseigné'}
                                                </p>
                                            )}
                                        </div>
                                        {element.photo?.url && <VignettesEdl photos={[element.photo]} libelle={`Photo de ${element.nom}`} />}
                                        <BadgeEtat etat={element.etat} />
                                    </li>
                                );
                            })}
                        </ul>
                        {piece.photos.length > 0 && (
                            <div className="border-t p-3">
                                <p className="mb-2 text-xs text-muted">Photos de la pièce</p>
                                <VignettesEdl photos={piece.photos} libelle={`Photo ${piece.nom}`} />
                            </div>
                        )}
                    </section>
                ))
            )}

            {constat.observation && (
                <section>
                    <h3 className="mb-1 text-xs text-muted">Observations générales</h3>
                    <p className="rounded-brand bg-surface p-3 text-sm whitespace-pre-line text-gray-900">{constat.observation}</p>
                </section>
            )}

            {signatures && (
                <section className="grid gap-3 sm:grid-cols-2" aria-label="Signatures">
                    <CadreSignature libelle="Le CROU" image={constat.signature_crou} attente="Non signé par le CROU" />
                    <CadreSignature libelle="L’étudiant" image={constat.signature_etudiant} attente="Non signé par l’étudiant" />
                </section>
            )}
        </div>
    );
}

/** Consultation d'un état des lieux d'entrée ou de sortie. */
export function ConsultationDialog({ cible, onClose }: { cible: { resident: ResidentEdl; type: 'entree' | 'sortie' } | null; onClose: () => void }) {
    const constat = cible ? (cible.type === 'entree' ? cible.resident.entree : cible.resident.sortie) : null;

    return (
        <Dialog open={!!cible && !!constat} onOpenChange={(ouvert) => !ouvert && onClose()}>
            <DialogContent className="max-w-3xl">
                {cible && constat && (
                    <>
                        <DialogHeader>
                            <DialogTitle>
                                État des lieux {cible.type === 'entree' ? 'd’entrée' : 'de sortie'} · {cible.resident.name}
                            </DialogTitle>
                            <DialogDescription>
                                {cible.type === 'entree' ? 'Établi par l’étudiant à son arrivée.' : 'Établi par le CROU ; chaque élément est comparé à l’état d’entrée.'}
                            </DialogDescription>
                        </DialogHeader>
                        <DialogBody>
                            <DetailConstat resident={cible.resident} constat={constat} reference={cible.type === 'sortie' ? cible.resident.entree : undefined} />
                        </DialogBody>
                        <DialogFooter>
                            {constat.urls.pdf && (
                                <a href={constat.urls.pdf} target="_blank" rel="noopener" className={cn(buttonVariants({ variant: 'outline', size: 'sm' }), 'h-10')}>
                                    <FileDown {...ICON} aria-hidden /> Télécharger le PDF
                                </a>
                            )}
                            <Button size="sm" className="h-10" onClick={onClose}>
                                Fermer
                            </Button>
                        </DialogFooter>
                    </>
                )}
            </DialogContent>
        </Dialog>
    );
}

/**
 * Signature par le CROU de l'état des lieux d'entrée de l'étudiant (`updateEdl`).
 * Seule la signature est envoyée : le constat de l'étudiant n'est pas modifié.
 */
export function SignatureEntreeDialog({ resident, onClose }: { resident: ResidentEdl | null; onClose: () => void }) {
    const entree = resident?.entree ?? null;
    const {
        control,
        reset,
        setError,
        handleSubmit,
        formState: { errors, isSubmitting },
    } = useForm<SignatureEntree>({ resolver: zodResolver(signatureEntreeSchema), defaultValues: { verifie: false as unknown as true, signature_crou: null } });

    useEffect(() => {
        if (resident) reset({ verifie: false as unknown as true, signature_crou: null });
    }, [resident, reset]);

    const signer = handleSubmit(async (valeurs) => {
        if (!resident || !entree?.urls.signer) return;
        const ok = await envoyer<SignatureEntree>(
            entree.urls.signer,
            {
                type_etat: 'entree',
                id_cite: resident.cite_id ?? '',
                id_batiment: resident.batiment_id ?? '',
                id_chambre: entree.chambre_id ?? resident.chambre_id ?? '',
                id_etudiant: resident.id,
                signature_crou: valeurs.signature_crou ?? '',
            },
            { method: 'put', setError, champs: ['signature_crou'] },
        );
        if (ok) onClose();
    });

    return (
        <Dialog open={!!resident && !!entree} onOpenChange={(ouvert) => !ouvert && !isSubmitting && onClose()}>
            <DialogContent className="max-w-3xl">
                {resident && entree && (
                    <form noValidate onSubmit={signer} className="flex min-h-0 flex-1 flex-col">
                        <DialogHeader>
                            <DialogTitle>Signer l’état des lieux d’entrée · {resident.name}</DialogTitle>
                            <DialogDescription>Vérifiez le constat de l’étudiant, puis signez au nom du CROU. Une fois signé, l’étudiant ne peut plus le modifier.</DialogDescription>
                        </DialogHeader>
                        <DialogBody className="space-y-5">
                            <DetailConstat resident={resident} constat={entree} signatures={false} />
                            <Controller
                                control={control}
                                name="verifie"
                                render={({ field }) => (
                                    <div className="space-y-1">
                                        <label className="flex cursor-pointer items-start gap-2.5 text-sm text-gray-800">
                                            <Checkbox checked={field.value === true} onCheckedChange={(c) => field.onChange(c === true)} aria-invalid={!!errors.verifie || undefined} className="mt-0.5" />
                                            J’ai vérifié ce constat avec l’étudiant et il correspond à l’état du logement.
                                        </label>
                                        {errors.verifie && (
                                            <p role="alert" className="text-xs font-medium text-red-600">
                                                {errors.verifie.message}
                                            </p>
                                        )}
                                    </div>
                                )}
                            />
                            <Field id="signature-entree" label="Signature du CROU" required error={errors.signature_crou?.message}>
                                <Controller
                                    control={control}
                                    name="signature_crou"
                                    render={({ field }) => <SignaturePad id="signature-entree" value={field.value} onChange={field.onChange} invalid={!!errors.signature_crou} />}
                                />
                            </Field>
                        </DialogBody>
                        <DialogFooter>
                            <Button variant="outline" size="sm" className="h-10" onClick={onClose} disabled={isSubmitting}>
                                Annuler
                            </Button>
                            <Button type="submit" size="sm" className="h-10" disabled={isSubmitting}>
                                {isSubmitting ? <Loader2 className="size-4 animate-spin" aria-hidden /> : <PenLine className="size-4" aria-hidden />}
                                Signer l’état d’entrée
                            </Button>
                        </DialogFooter>
                    </form>
                )}
            </DialogContent>
        </Dialog>
    );
}

