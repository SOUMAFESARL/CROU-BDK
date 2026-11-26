import { zodResolver } from '@hookform/resolvers/zod';
import { FileCheck, Power } from 'lucide-react';
import { useState } from 'react';
import { Controller, useForm, type Path } from 'react-hook-form';
import { ErreurGenerale, PiedAssistant, Recap, useAssistant } from '@/components/parametrage-hebergement/shared';
import { ToggleCard } from '@/components/parc-immobilier/form/inputs';
import { ChampsManquants } from '@/components/ui/champs-manquants';
import { repartirErreurs } from '@/components/vie-residentielle/shared';
import { Badge } from '@/components/ui/badge';
import { DialogBody, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Stepper } from '@/components/ui/stepper';
import { Textarea } from '@/components/ui/textarea';
import { champsManquants } from '@/lib/champs-manquants';
import { envoyer } from '@/lib/inertia-form';
import { nombre } from '@/lib/text';
import { documentSchema, enNombre, type DocumentValeurs } from '@/schemas/parametrage-hebergement';

/** Champs du document modifiables dans le formulaire. */
export type DocumentParametre = {
    name: string;
    description: string | null;
    ordre_affiche: number | null;
    delai_validite_mois: number | null;
    requis: boolean;
    actif: boolean;
};

export const validite = (mois: number | null) => (mois ? `${nombre(mois)} mois` : 'Sans limite');

const ETAPES = ['Informations', 'Règles', 'Vérification'];
const CHAMPS: Path<DocumentValeurs>[][] = [['name', 'description'], ['ordre_affiche', 'delai_validite_mois', 'requis', 'actif'], []];

/**
 * Formulaire d'ajout / de modification d'un document à fournir (actions `document.store` / `document.update`).
 * Partagé par la page « Documents à fournir » et l'assistant de campagne (ajout sans quitter la campagne).
 */
export function DocumentFormulaire({
    document,
    url,
    ordreSuivant,
    onClose,
    errorBag,
}: {
    document: DocumentParametre | null;
    url: string | null;
    ordreSuivant: number;
    onClose: () => void;
    /** Sac d'erreurs séparé quand le formulaire est ouvert dans une autre page (assistant de campagne). */
    errorBag?: string;
}) {
    const [erreur, setErreur] = useState<string | null>(null);
    const {
        register,
        control,
        trigger,
        watch,
        setError,
        handleSubmit,
        formState: { errors, isSubmitting },
    } = useForm<DocumentValeurs>({
        resolver: zodResolver(documentSchema),
        mode: 'onTouched',
        defaultValues: {
            name: document?.name ?? '',
            description: document?.description ?? '',
            ordre_affiche: String(document?.ordre_affiche ?? ordreSuivant),
            delai_validite_mois: document?.delai_validite_mois ? String(document.delai_validite_mois) : '',
            requis: document?.requis ?? true,
            actif: document?.actif ?? true,
        },
    });
    const { etape, atteinte, aller, allerA, toutValider, derniere } = useAssistant<DocumentValeurs>(CHAMPS, trigger, document !== null);
    const v = watch();
    const manquants = champsManquants(documentSchema, v, CHAMPS, { name: 'Nom du document' });

    // L'action attend des cases à cocher HTML (« on ») et du texte pour l'ordre et le délai ; le statut n'existe qu'en modification.
    const enregistrer = (valeurs: DocumentValeurs) =>
        envoyer<DocumentValeurs>(
            url!,
            {
                name: valeurs.name.trim(),
                description: valeurs.description.trim(),
                ordre_affiche: String(enNombre(valeurs.ordre_affiche)),
                delai_validite_mois: valeurs.delai_validite_mois.trim() === '' ? '' : String(enNombre(valeurs.delai_validite_mois)),
                ...(valeurs.requis ? { champs_requis: 'on' } : {}),
                ...(document && valeurs.actif ? { statut: 'on' } : {}),
            },
            {
                method: document ? 'put' : 'post',
                errorBag,
                onSuccess: onClose,
                onError: (erreurs) =>
                    setErreur(
                        repartirErreurs(
                            Object.fromEntries(
                                Object.entries(erreurs).map(([cle, message]) => [cle === 'champs_requis' ? 'requis' : cle === 'statut' ? 'actif' : cle, message]),
                            ),
                            CHAMPS,
                            (champ, e) => setError(champ, e),
                            aller,
                        ),
                    ),
            },
        );

    return (
        <form
            noValidate
            className="flex min-h-0 flex-col"
            onSubmit={async (event) => {
                event.preventDefault();
                setErreur(null);
                if (!derniere) await allerA(etape + 1);
                else if (await toutValider()) await handleSubmit(enregistrer)();
            }}
        >
            <DialogHeader>
                <DialogTitle>{document ? `Modifier « ${document.name} »` : 'Ajouter un document'}</DialogTitle>
                <DialogDescription>
                    Étape {etape + 1} sur {ETAPES.length}
                </DialogDescription>
            </DialogHeader>
            <Stepper steps={ETAPES} current={etape} reached={atteinte} onStepClick={(index) => void allerA(index)} />
            <DialogBody className="space-y-4">
                <ErreurGenerale message={erreur} />

                {etape === 0 && (
                    <>
                        <Field id="doc-name" label="Nom du document" required error={errors.name?.message}>
                            <Input id="doc-name" maxLength={100} placeholder="Ex. Relevé de notes" invalid={!!errors.name} autoFocus {...register('name')} />
                        </Field>
                        <Field id="doc-description" label="Description" error={errors.description?.message} hint="Consigne affichée à l’étudiant (facultative)">
                            <Textarea id="doc-description" rows={3} maxLength={2000} placeholder="Ex. Relevé de l’année précédente, signé" invalid={!!errors.description} {...register('description')} />
                        </Field>
                    </>
                )}

                {etape === 1 && (
                    <>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field id="doc-ordre" label="Ordre d’affichage" required error={errors.ordre_affiche?.message}>
                                <Input id="doc-ordre" inputMode="numeric" placeholder="Ex. 1" invalid={!!errors.ordre_affiche} {...register('ordre_affiche')} />
                            </Field>
                            <Field id="doc-validite" label="Validité (mois)" error={errors.delai_validite_mois?.message} hint="Vide : sans limite">
                                <Input id="doc-validite" inputMode="numeric" placeholder="Ex. 12" invalid={!!errors.delai_validite_mois} {...register('delai_validite_mois')} />
                            </Field>
                        </div>
                        <Controller
                            control={control}
                            name="requis"
                            render={({ field }) => (
                                <ToggleCard
                                    checked={field.value}
                                    onChange={field.onChange}
                                    label="Pièce obligatoire"
                                    icon={<FileCheck />}
                                    description="L’étudiant doit joindre ce document à sa demande de logement."
                                />
                            )}
                        />
                        {document && (
                            <Controller
                                control={control}
                                name="actif"
                                render={({ field }) => (
                                    <ToggleCard
                                        checked={field.value}
                                        onChange={field.onChange}
                                        label="Document actif"
                                        icon={<Power />}
                                        description="Un document inactif n’est plus proposé dans les nouvelles campagnes."
                                    />
                                )}
                            />
                        )}
                    </>
                )}

                {etape === 2 && (
                    <>
                        <ChampsManquants manquants={manquants} etapes={ETAPES} onAller={aller} />
                        <Recap
                            lignes={[
                                { label: 'Nom', valeur: <span className="font-medium">{v.name.trim()}</span> },
                                { label: 'Description', valeur: v.description.trim() || <span className="text-muted">Aucune</span> },
                                { label: 'Ordre d’affichage', valeur: v.ordre_affiche },
                                { label: 'Validité', valeur: validite(enNombre(v.delai_validite_mois)) },
                                { label: 'Pièce', valeur: v.requis ? <Badge variant="warning">Obligatoire</Badge> : <Badge variant="neutral">Facultative</Badge> },
                                { label: 'Statut', valeur: !document || v.actif ? <Badge variant="success">Actif</Badge> : <Badge variant="neutral">Inactif</Badge> },
                            ]}
                        />
                    </>
                )}
            </DialogBody>
            <PiedAssistant
                etape={etape}
                derniere={derniere}
                envoi={isSubmitting}
                desactive={!url || manquants.length > 0}
                onAnnuler={onClose}
                onPrecedent={() => aller(etape - 1)}
                libelle={document ? 'Enregistrer les modifications' : 'Ajouter le document'}
            />
        </form>
    );
}
