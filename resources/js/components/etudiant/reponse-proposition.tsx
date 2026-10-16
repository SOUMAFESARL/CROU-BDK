import { zodResolver } from '@hookform/resolvers/zod';
import { CircleCheck, CircleX, Loader2, TriangleAlert } from 'lucide-react';
import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { z } from 'zod';
import { Notice } from '@/components/etudiant/panneau';
import { ICON } from '@/components/layout/app-sidebar';
import { Button } from '@/components/ui/button';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Field } from '@/components/ui/field';
import { Textarea } from '@/components/ui/textarea';
import { envoyer } from '@/lib/inertia-form';
import { cn } from '@/lib/utils';

export type Proposition = {
    url: string;
    assignationId: string;
    chambre: string | null;
    annee: string | null;
    limite: string | null;
};

const MAX_MOTIF = 500;

/** Mêmes règles que `dashboardController::AcceptOrRefuseDemande` (le serveur revérifie, DossierWorkflow décide). */
const refusSchema = z.object({
    motif_refus: z.string().trim().min(1, 'Indiquez le motif du refus.').max(MAX_MOTIF, `Le motif ne doit pas dépasser ${MAX_MOTIF} caractères.`),
});

type Refus = z.infer<typeof refusSchema>;

/** Boutons « Accepter l'offre » / « Refuser » et leurs fenêtres de confirmation. */
export function ReponseProposition({ proposition }: { proposition: Proposition }) {
    const [ouverte, setOuverte] = useState<'accepter' | 'refuser' | null>(null);

    return (
        <>
            <div className="flex flex-col gap-2 sm:flex-row">
                <Button onClick={() => setOuverte('accepter')} className="font-medium">
                    <CircleCheck {...ICON} />
                    Accepter l’offre
                </Button>
                <Button variant="outline" onClick={() => setOuverte('refuser')} className="font-medium text-red-700 hover:bg-red-50">
                    <CircleX {...ICON} />
                    Refuser
                </Button>
            </div>
            <Acceptation proposition={proposition} ouverte={ouverte === 'accepter'} fermer={() => setOuverte(null)} />
            <RefusDialog proposition={proposition} ouverte={ouverte === 'refuser'} fermer={() => setOuverte(null)} />
        </>
    );
}

function Details({ proposition }: { proposition: Proposition }) {
    const lignes: [string, string | null][] = [
        ['Année académique', proposition.annee],
        ['Chambre proposée', proposition.chambre],
        ['Répondre avant le', proposition.limite],
    ];

    return (
        <dl className="divide-y rounded-brand border">
            {lignes
                .filter(([, valeur]) => valeur)
                .map(([libelle, valeur]) => (
                    <div key={libelle} className="flex flex-wrap justify-between gap-x-4 gap-y-0.5 px-3 py-2.5 text-sm">
                        <dt className="text-muted">{libelle}</dt>
                        <dd className="font-medium text-gray-900">{valeur}</dd>
                    </div>
                ))}
        </dl>
    );
}

type DialogProps = { proposition: Proposition; ouverte: boolean; fermer: () => void };

function Acceptation({ proposition, ouverte, fermer }: DialogProps) {
    const [envoi, setEnvoi] = useState(false);

    const accepter = async () => {
        setEnvoi(true);
        const ok = await envoyer(proposition.url, { action: 'accepter', assignation_id: proposition.assignationId });
        setEnvoi(false);
        if (ok) fermer();
    };

    return (
        <Dialog open={ouverte} onOpenChange={(valeur) => !valeur && !envoi && fermer()}>
            <DialogContent className="max-w-lg">
                <DialogHeader>
                    <DialogTitle>Accepter la proposition</DialogTitle>
                    <DialogDescription>
                        Vous confirmez votre résidence pour l’année académique {proposition.annee ?? 'en cours'}. La validation définitive relève du service Logement.
                    </DialogDescription>
                </DialogHeader>
                <DialogBody>
                    <Details proposition={proposition} />
                </DialogBody>
                <DialogFooter>
                    <Button variant="outline" onClick={fermer} disabled={envoi}>
                        Annuler
                    </Button>
                    <Button onClick={accepter} disabled={envoi} className="font-medium">
                        {envoi ? <Loader2 {...ICON} className="animate-spin" /> : <CircleCheck {...ICON} />}
                        Confirmer l’acceptation
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function RefusDialog({ proposition, ouverte, fermer }: DialogProps) {
    const {
        register,
        handleSubmit,
        setError,
        reset,
        watch,
        formState: { errors, isSubmitting },
    } = useForm<Refus>({ resolver: zodResolver(refusSchema), defaultValues: { motif_refus: '' }, mode: 'onTouched' });
    const longueur = watch('motif_refus').length;

    const fermerEtVider = () => {
        reset();
        fermer();
    };

    const refuser = handleSubmit(async (donnees) => {
        const ok = await envoyer<Refus>(
            proposition.url,
            { action: 'refuser', assignation_id: proposition.assignationId, motif_refus: donnees.motif_refus },
            { setError, champs: ['motif_refus'] },
        );
        if (ok) fermerEtVider();
    });

    return (
        <Dialog open={ouverte} onOpenChange={(valeur) => !valeur && !isSubmitting && fermerEtVider()}>
            <DialogContent className="max-w-lg">
                <form onSubmit={refuser} noValidate className="flex min-h-0 flex-col">
                    <DialogHeader>
                        <DialogTitle>Refuser la proposition</DialogTitle>
                        <DialogDescription>Indiquez pourquoi vous refusez la chambre proposée.</DialogDescription>
                    </DialogHeader>
                    <DialogBody className="flex flex-col gap-4">
                        <Notice icone={TriangleAlert} className="bg-amber-50 text-amber-800">
                            En refusant cette offre, vous ne recevrez pas d’hébergement pour cette année académique.
                        </Notice>
                        <Details proposition={proposition} />
                        <Field
                            id="motif_refus"
                            label="Motif du refus"
                            required
                            error={errors.motif_refus?.message}
                            hint={
                                <span className={cn(longueur > MAX_MOTIF - 50 && 'text-red-700')}>
                                    {longueur} / {MAX_MOTIF} caractères
                                </span>
                            }
                        >
                            <Textarea
                                id="motif_refus"
                                rows={4}
                                maxLength={MAX_MOTIF}
                                placeholder="Expliquez pourquoi vous refusez cette offre…"
                                invalid={!!errors.motif_refus}
                                aria-describedby={errors.motif_refus ? 'motif_refus-error' : 'motif_refus-hint'}
                                {...register('motif_refus')}
                            />
                        </Field>
                    </DialogBody>
                    <DialogFooter>
                        <Button variant="outline" onClick={fermerEtVider} disabled={isSubmitting}>
                            Annuler
                        </Button>
                        <Button type="submit" disabled={isSubmitting} className="bg-red-700 font-medium hover:bg-red-800">
                            {isSubmitting ? <Loader2 {...ICON} className="animate-spin" /> : <CircleX {...ICON} />}
                            Confirmer le refus
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
