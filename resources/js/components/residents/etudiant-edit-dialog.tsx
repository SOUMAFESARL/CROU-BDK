import { zodResolver } from '@hookform/resolvers/zod';
import { ArrowLeft, ArrowRight, Loader2, Save } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Controller, useForm } from 'react-hook-form';
import { z } from 'zod';
import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Stepper } from '@/components/ui/stepper';
import { aujourdhui, Encadre, repartirErreurs } from '@/components/vie-residentielle/shared';
import { envoyer } from '@/lib/inertia-form';

const requis = (message: string) => z.string().trim().min(1, message);
const optionnel = (max: number) => z.string().trim().max(max, `${max} caractères au maximum.`);
const email = z.string().trim().max(255, '255 caractères au maximum.').refine((valeur) => valeur === '' || z.email().safeParse(valeur).success, 'Adresse e-mail invalide.');

const schema = z.object({
    name: requis('Le nom et les prénoms sont obligatoires.').max(255, '255 caractères au maximum.'),
    genre_id: requis('Choisissez la civilité.'),
    matricule: requis('Le matricule est obligatoire.').max(100, '100 caractères au maximum.'),
    numero_etudiant: optionnel(100),
    date_naissance: z.string(),
    lieu_naissance: optionnel(255),
    nationalite: optionnel(100),
    telephone1: requis('Le téléphone principal est obligatoire.').max(30, '30 caractères au maximum.'),
    telephone2: optionnel(30),
    email1: email,
    email2: email,
    adresse: optionnel(255),
    name_tuteur: optionnel(255),
    telephone_tuteur: optionnel(30),
    adresse_tuteur: optionnel(255),
});

export type ValeursEtudiant = z.infer<typeof schema>;

const CHAMPS_ETAPES: (keyof ValeursEtudiant)[][] = [
    ['name', 'genre_id', 'matricule', 'numero_etudiant', 'date_naissance', 'lieu_naissance', 'nationalite'],
    ['telephone1', 'telephone2', 'email1', 'email2', 'adresse'],
    ['name_tuteur', 'telephone_tuteur', 'adresse_tuteur'],
];
const ETAPES = ['Identité', 'Coordonnées', 'Tuteur'];

type Props = {
    /** Étudiant à modifier (fenêtre fermée si `null`). */
    etudiant: { name: string; url: string; valeurs: ValeursEtudiant } | null;
    genres: { id: string; name: string }[];
    onClose: () => void;
};

/** Modification des informations personnelles d'un étudiant (action `residents_contrat.update_etudiant`). */
export function EtudiantEditDialog({ etudiant, genres, onClose }: Props) {
    const [etape, setEtape] = useState(0);
    const [atteinte, setAtteinte] = useState(0);
    const [erreurServeur, setErreurServeur] = useState<string | null>(null);
    const {
        control,
        register,
        handleSubmit,
        reset,
        trigger,
        setError,
        formState: { errors, isSubmitting },
    } = useForm<ValeursEtudiant>({ resolver: zodResolver(schema), mode: 'onTouched' });

    useEffect(() => {
        if (etudiant) {
            reset(etudiant.valeurs);
            setEtape(0);
            setAtteinte(0);
            setErreurServeur(null);
        }
    }, [etudiant, reset]);

    // Valide toutes les étapes jusqu'à `cible` (exclue) avant d'y aller.
    const allerA = async (cible: number) => {
        if (cible <= etape) {
            setEtape(cible);
            return;
        }
        for (let index = etape; index < cible; index++) {
            if (!(await trigger(CHAMPS_ETAPES[index]))) {
                setEtape(index);
                return;
            }
        }
        setEtape(cible);
        setAtteinte((max) => Math.max(max, cible));
    };

    const onSubmit = (valeurs: ValeursEtudiant) => {
        if (!etudiant) return;
        setErreurServeur(null);
        return envoyer(etudiant.url, valeurs, {
            method: 'put',
            onSuccess: onClose,
            onError: (erreurs) => setErreurServeur(repartirErreurs(erreurs, CHAMPS_ETAPES, setError, setEtape)),
        });
    };

    const champ = (nom: keyof ValeursEtudiant, label: string, options: { required?: boolean; type?: string; placeholder?: string } = {}) => (
        <Field id={`ed-${nom}`} label={label} required={options.required} error={errors[nom]?.message}>
            <Input id={`ed-${nom}`} type={options.type ?? 'text'} placeholder={options.placeholder} invalid={!!errors[nom]} className="h-11" {...register(nom)} />
        </Field>
    );

    return (
        <Dialog open={!!etudiant} onOpenChange={(ouvert) => !ouvert && !isSubmitting && onClose()}>
            <DialogContent className="max-w-2xl">
                <form
                    onSubmit={handleSubmit(onSubmit, (fautes) => setEtape(Math.max(0, CHAMPS_ETAPES.findIndex((champs) => champs.some((nom) => fautes[nom])))))}
                    noValidate
                    className="flex min-h-0 flex-col"
                >
                    <DialogHeader>
                        <DialogTitle>Modifier les informations de l’étudiant</DialogTitle>
                        <DialogDescription>
                            {etudiant?.name} · Étape {etape + 1} sur {ETAPES.length} · Les champs marqués <span className="text-red-600">*</span> sont obligatoires.
                        </DialogDescription>
                    </DialogHeader>
                    <Stepper steps={ETAPES} current={etape} reached={atteinte} onStepClick={allerA} />
                    <DialogBody className="space-y-4">
                        {erreurServeur && <Encadre ton="danger">{erreurServeur}</Encadre>}

                        {etape === 0 && (
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div className="sm:col-span-2">{champ('name', 'Nom et prénoms', { required: true })}</div>
                                <Field id="ed-genre_id" label="Civilité" required error={errors.genre_id?.message}>
                                    <Controller
                                        control={control}
                                        name="genre_id"
                                        render={({ field }) => (
                                            <Select value={field.value || undefined} onValueChange={field.onChange}>
                                                <SelectTrigger id="ed-genre_id" invalid={!!errors.genre_id} onBlur={field.onBlur}>
                                                    <SelectValue placeholder="Sélectionner" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {genres.map((genre) => (
                                                        <SelectItem key={genre.id} value={genre.id}>
                                                            {genre.name}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        )}
                                    />
                                </Field>
                                {champ('matricule', 'Matricule', { required: true })}
                                {champ('numero_etudiant', 'Numéro étudiant')}
                                <Field id="ed-date_naissance" label="Date de naissance" error={errors.date_naissance?.message}>
                                    <Controller
                                        control={control}
                                        name="date_naissance"
                                        render={({ field }) => (
                                            <DatePicker id="ed-date_naissance" value={field.value} onChange={field.onChange} max={aujourdhui()} listesMoisAnnee invalid={!!errors.date_naissance} />
                                        )}
                                    />
                                </Field>
                                {champ('lieu_naissance', 'Lieu de naissance')}
                                {champ('nationalite', 'Nationalité', { placeholder: 'Ex. IVOIRIENNE' })}
                            </div>
                        )}

                        {etape === 1 && (
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                {champ('telephone1', 'Téléphone principal', { required: true, type: 'tel' })}
                                {champ('telephone2', 'Téléphone secondaire', { type: 'tel' })}
                                {champ('email1', 'E-mail principal', { type: 'email' })}
                                {champ('email2', 'E-mail secondaire', { type: 'email' })}
                                <div className="sm:col-span-2">{champ('adresse', 'Adresse')}</div>
                            </div>
                        )}

                        {etape === 2 && (
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div className="sm:col-span-2">{champ('name_tuteur', 'Nom du tuteur')}</div>
                                {champ('telephone_tuteur', 'Téléphone du tuteur', { type: 'tel' })}
                                {champ('adresse_tuteur', 'Adresse du tuteur')}
                            </div>
                        )}
                    </DialogBody>
                    <DialogFooter className="sm:justify-between">
                        <Button variant="outline" size="sm" className="h-10" onClick={onClose} disabled={isSubmitting}>
                            Annuler
                        </Button>
                        <div className="flex flex-col-reverse gap-2 sm:flex-row">
                            {etape > 0 && (
                                <Button variant="outline" size="sm" className="h-10" onClick={() => setEtape(etape - 1)} disabled={isSubmitting}>
                                    <ArrowLeft className="size-4" aria-hidden /> Précédent
                                </Button>
                            )}
                            {etape < ETAPES.length - 1 ? (
                                <Button size="sm" className="h-10" onClick={() => allerA(etape + 1)}>
                                    Suivant <ArrowRight className="size-4" aria-hidden />
                                </Button>
                            ) : (
                                <Button type="submit" size="sm" className="h-10" disabled={isSubmitting}>
                                    {isSubmitting ? <Loader2 className="size-4 animate-spin" aria-hidden /> : <Save className="size-4" aria-hidden />}
                                    Enregistrer
                                </Button>
                            )}
                        </div>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
