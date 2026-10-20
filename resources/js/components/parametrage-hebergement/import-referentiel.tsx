import { ArrowLeft, ArrowRight, Download, Loader2, TriangleAlert, Upload } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button, buttonVariants } from '@/components/ui/button';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Field } from '@/components/ui/field';
import { FileDrop } from '@/components/ui/file-drop';
import { Stepper } from '@/components/ui/stepper';
import { envoyer } from '@/lib/inertia-form';
import { cn } from '@/lib/utils';

export type ColonneImport = { nom: string; requise?: boolean; aide: string };

/** Rapport renvoyé par l'action `import` quand des lignes ont été refusées. */
export type RapportImport = { message: string; lignes: string[] } | null;

const ETAPES = ['Modèle', 'Fichier'];

/** Bouton « Importer » + assistant en 2 étapes (modèle à remplir, puis envoi du fichier .xlsx / .csv). */
export function ImportReferentiel({ url, modele, titre, colonnes, note }: { url: string; modele: string | null; titre: string; colonnes: ColonneImport[]; note?: string }) {
    const [ouvert, setOuvert] = useState(false);
    const [etape, setEtape] = useState(0);
    const [fichier, setFichier] = useState<File | null>(null);
    const [erreur, setErreur] = useState<string | null>(null);
    const [enCours, setEnCours] = useState(false);

    const fermer = () => {
        setOuvert(false);
        setEtape(0);
        setFichier(null);
        setErreur(null);
    };

    const importer = async () => {
        if (!fichier) return setErreur('Choisissez un fichier .xlsx ou .csv.');
        if (!/\.(xlsx|csv|txt)$/i.test(fichier.name)) return setErreur('Formats acceptés : .xlsx ou .csv.');
        if (fichier.size > 5 * 1024 * 1024) return setErreur('Le fichier dépasse 5 Mo.');
        setEnCours(true);
        const ok = await envoyer(url, { fichier }, { forceFormData: true, onError: (erreurs) => setErreur(erreurs.fichier ?? Object.values(erreurs)[0] ?? null) });
        setEnCours(false);
        if (ok) fermer();
    };

    return (
        <>
            <Button variant="outline" size="sm" className="h-10" onClick={() => setOuvert(true)}>
                <Upload className="size-4" aria-hidden /> Importer
            </Button>
            <Dialog open={ouvert} onOpenChange={(o) => !o && !enCours && fermer()}>
                <DialogContent className="max-w-xl">
                    <form
                        noValidate
                        className="flex min-h-0 flex-col"
                        onSubmit={(event) => {
                            event.preventDefault();
                            if (etape === 0) setEtape(1);
                            else void importer();
                        }}
                    >
                        <DialogHeader>
                            <DialogTitle>{titre}</DialogTitle>
                            <DialogDescription>
                                Étape {etape + 1} sur {ETAPES.length}
                            </DialogDescription>
                        </DialogHeader>
                        <Stepper steps={ETAPES} current={etape} reached={1} onStepClick={setEtape} />
                        <DialogBody className="space-y-4">
                            {etape === 0 ? (
                                <>
                                    <div className="flex items-center justify-between gap-3 rounded-brand border bg-surface/50 px-4 py-3">
                                        <div>
                                            <p className="text-sm font-medium text-gray-900">Modèle Excel</p>
                                            <p className="text-xs text-muted">Une ligne par élément, en conservant les en-têtes.</p>
                                        </div>
                                        {modele && (
                                            <a href={modele} className={cn(buttonVariants({ variant: 'secondary', size: 'sm' }), 'h-10')}>
                                                <Download className="size-4" aria-hidden /> Télécharger
                                            </a>
                                        )}
                                    </div>
                                    <ul className="divide-y rounded-brand border">
                                        {colonnes.map((c) => (
                                            <li key={c.nom} className="flex items-start gap-3 px-4 py-2.5 text-sm">
                                                <code className="w-28 shrink-0 font-medium text-gray-900">{c.nom}</code>
                                                <span className="flex-1 text-muted">{c.aide}</span>
                                                <Badge variant={c.requise ? 'success' : 'neutral'}>{c.requise ? 'Obligatoire' : 'Facultative'}</Badge>
                                            </li>
                                        ))}
                                    </ul>
                                    {note && <p className="text-xs text-muted">{note}</p>}
                                </>
                            ) : (
                                <Field id="import-fichier" label="Fichier à importer" required error={erreur ?? undefined}>
                                    <FileDrop
                                        id="import-fichier"
                                        file={fichier}
                                        onChange={(choisi) => {
                                            setFichier(choisi);
                                            setErreur(null);
                                        }}
                                        accept=".xlsx,.csv,.txt"
                                        title="Choisissez un fichier"
                                        hint="Formats .xlsx ou .csv, 5 Mo maximum."
                                        invalid={!!erreur}
                                    />
                                </Field>
                            )}
                        </DialogBody>
                        <DialogFooter className="sm:justify-between">
                            <Button type="button" variant="outline" size="sm" className="h-10" onClick={fermer} disabled={enCours}>
                                Annuler
                            </Button>
                            <div className="flex flex-col-reverse gap-2 sm:flex-row">
                                {etape > 0 && (
                                    <Button type="button" variant="outline" size="sm" className="h-10" onClick={() => setEtape(0)} disabled={enCours}>
                                        <ArrowLeft className="size-4" aria-hidden /> Précédent
                                    </Button>
                                )}
                                {etape === 0 ? (
                                    <Button type="submit" size="sm" className="h-10">
                                        Suivant <ArrowRight className="size-4" aria-hidden />
                                    </Button>
                                ) : (
                                    <Button type="submit" size="sm" className="h-10" disabled={enCours}>
                                        {enCours ? <Loader2 className="size-4 animate-spin" aria-hidden /> : <Upload className="size-4" aria-hidden />}
                                        Importer
                                    </Button>
                                )}
                            </div>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}

/** Lignes refusées par le dernier import, affichées en fenêtre dès le retour sur la liste. */
export function RapportImportDialog({ rapport }: { rapport: RapportImport }) {
    const [ouvert, setOuvert] = useState(true);
    if (!rapport || rapport.lignes.length === 0) return null;

    return (
        <Dialog open={ouvert} onOpenChange={setOuvert}>
            <DialogContent className="max-w-xl">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <span className="flex size-8 items-center justify-center rounded-brand bg-red-50 text-red-700" aria-hidden>
                            <TriangleAlert size={18} strokeWidth={1.25} />
                        </span>
                        {rapport.lignes.length} ligne(s) non enregistrée(s)
                    </DialogTitle>
                    <DialogDescription>{rapport.message}</DialogDescription>
                </DialogHeader>
                <DialogBody className="text-sm">
                    <ul className="list-disc space-y-1.5 pl-5">
                        {rapport.lignes.map((ligne) => (
                            <li key={ligne}>{ligne}</li>
                        ))}
                    </ul>
                    <p className="mt-4 text-xs text-muted">Corrigez ces lignes puis importez de nouveau le fichier : les lignes déjà enregistrées ne seront pas dupliquées.</p>
                </DialogBody>
                <DialogFooter>
                    <Button size="sm" className="h-10" onClick={() => setOuvert(false)}>
                        Fermer
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
