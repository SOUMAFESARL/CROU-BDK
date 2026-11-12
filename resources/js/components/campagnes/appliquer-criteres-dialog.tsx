import { CheckCheck } from 'lucide-react';
import { useEffect, useState } from 'react';
import { ConfirmDialog } from '@/components/feedback/confirm-dialog';
import { envoyer } from '@/lib/inertia-form';
import { JEUX, type EtatCriteres, type Jeu } from './criteres';

type Props = {
    /** URL d'application ; null ferme la fenêtre. */
    url: string | null;
    volet: Jeu;
    nomCampagne: string;
    etat: EtatCriteres | null;
    onClose: () => void;
};

/**
 * Confirmation de la mise en vigueur des critères d'un volet : le brouillon, ou le modèle CROU de Bondoukou si rien n'est encore saisi.
 * Si une version est déjà en vigueur, elle est simplement modifiée (rien n'est archivé).
 */
export function AppliquerCriteresDialog({ url, volet, nomCampagne, etat, onClose }: Props) {
    const [erreur, setErreur] = useState<string | null>(null);
    const [traitement, setTraitement] = useState(false);

    useEffect(() => {
        if (url) setErreur(null);
    }, [url]);

    const confirmer = async () => {
        if (!url) return;
        setTraitement(true);
        const ok = await envoyer(url, {}, {
            onError: (erreurs) => setErreur(erreurs.criteres ?? Object.values(erreurs)[0] ?? 'Les critères n’ont pas pu être appliqués.'),
        });
        setTraitement(false);
        if (ok) onClose();
    };

    return (
        <ConfirmDialog
            open={url !== null}
            onOpenChange={(ouvert) => !ouvert && onClose()}
            title={`Appliquer les critères ${JEUX[volet].complement} ?`}
            description={
                <div className="space-y-2">
                    <p>
                        Campagne « {nomCampagne} ».{' '}
                        {etat?.brouillon
                            ? 'Le brouillon enregistré devient la version en vigueur.'
                            : 'Le modèle CROU de Bondoukou est appliqué tel quel ; vous pourrez le modifier ensuite.'}
                    </p>
                    {erreur && (
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
        />
    );
}
