import { CheckCheck } from 'lucide-react';
import { useEffect, useState } from 'react';
import { ConfirmDialog } from '@/components/feedback/confirm-dialog';
import { Field } from '@/components/ui/field';
import { Textarea } from '@/components/ui/textarea';
import { envoyer } from '@/lib/inertia-form';
import { VOLETS, type EtatCriteres, type Volet } from './criteres';

type Props = {
    /** URL d'application ; null ferme la fenêtre. */
    url: string | null;
    volet: Volet;
    nomCampagne: string;
    etat: EtatCriteres | null;
    onClose: () => void;
};

/**
 * Confirmation de la mise en vigueur des critères d'un volet : le brouillon, ou le modèle CROU de Bondoukou si rien n'est encore saisi.
 * Remplacer une version en vigueur exige un motif (tracé dans l'historique des versions).
 */
export function AppliquerCriteresDialog({ url, volet, nomCampagne, etat, onClose }: Props) {
    const [motif, setMotif] = useState('');
    const [erreur, setErreur] = useState<string | null>(null);
    const [traitement, setTraitement] = useState(false);
    const remplacement = etat?.etat === 'en_vigueur';
    const motifManquant = remplacement && motif.trim().length < 5;

    useEffect(() => {
        if (url) {
            setMotif('');
            setErreur(null);
        }
    }, [url]);

    const confirmer = async () => {
        if (!url) return;
        if (motifManquant) {
            setErreur('Indiquez le motif du remplacement (5 caractères au minimum).');
            return;
        }
        setTraitement(true);
        const ok = await envoyer(url, { motif: motif.trim() || null }, {
            onError: (erreurs) => setErreur(erreurs.motif ?? erreurs.criteres ?? Object.values(erreurs)[0] ?? 'Les critères n’ont pas pu être appliqués.'),
        });
        setTraitement(false);
        if (ok) onClose();
    };

    return (
        <ConfirmDialog
            open={url !== null}
            onOpenChange={(ouvert) => !ouvert && onClose()}
            title={`Appliquer les critères ${VOLETS[volet].complement} ?`}
            description={
                <div className="space-y-2">
                    <p>
                        Campagne « {nomCampagne} ».{' '}
                        {etat?.brouillon
                            ? 'Le brouillon enregistré devient la version en vigueur.'
                            : 'Le modèle CROU de Bondoukou est appliqué tel quel ; vous pourrez le modifier ensuite.'}
                    </p>
                    {remplacement && <p>La version {etat?.version} en vigueur sera archivée.</p>}
                    {erreur && !remplacement && (
                        <p role="alert" className="text-sm font-medium text-red-600">
                            {erreur}
                        </p>
                    )}
                </div>
            }
            confirmLabel="Appliquer"
            confirmIcon={<CheckCheck className="size-4" aria-hidden />}
            processing={traitement}
            onConfirm={() => void confirmer()}
        >
            {remplacement && (
                <Field id="motif-application" label="Motif du remplacement" required error={erreur ?? undefined} hint="Inscrit dans l’historique des versions.">
                    <Textarea
                        id="motif-application"
                        rows={3}
                        maxLength={1000}
                        value={motif}
                        onChange={(event) => {
                            setMotif(event.target.value);
                            if (erreur) setErreur(null);
                        }}
                        placeholder="Ex : quotas arrêtés par le comité de direction du 12 novembre"
                        invalid={!!erreur}
                    />
                </Field>
            )}
        </ConfirmDialog>
    );
}
