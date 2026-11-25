import { zodResolver } from '@hookform/resolvers/zod';
import { Eye, Loader2 } from 'lucide-react';
import { useEffect } from 'react';
import { useForm, useWatch } from 'react-hook-form';
import { ICON } from '@/components/layout/app-sidebar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { envoyer } from '@/lib/inertia-form';
import { cn } from '@/lib/utils';
import { campagnesVisiblesSchema, type CampagnesVisibles } from '@/schemas/resultat-admission';

export type CampagneResultats = {
    id: string;
    libelle: string;
    active: boolean;
    /** Campagne supprimée : consultable dans la liste, mais ne peut pas être rendue visible. */
    supprimee: boolean;
    /** Résultats visibles par les étudiants. */
    visible: boolean;
};

type Props = {
    open: boolean;
    onClose: () => void;
    campagnes: CampagneResultats[];
    url: string;
};

/** Choix des campagnes dont les résultats sont visibles par les étudiants (cases à cocher, une au moins). */
export function CampagnesVisiblesDialog({ open, onClose, campagnes, url }: Props) {
    const proposees = campagnes.filter((campagne) => !campagne.supprimee);
    const {
        control,
        handleSubmit,
        reset,
        setValue,
        setError,
        formState: { errors, isSubmitting },
    } = useForm<CampagnesVisibles>({ resolver: zodResolver(campagnesVisiblesSchema), defaultValues: { campagnes: [] } });
    const choisies = useWatch({ control, name: 'campagnes' });

    // À l'ouverture : les campagnes déjà visibles sont cochées.
    useEffect(() => {
        if (open) reset({ campagnes: proposees.filter((campagne) => campagne.visible).map((campagne) => campagne.id) });
    }, [open, reset]);

    const cocher = (id: string, coche: boolean) =>
        setValue('campagnes', coche ? [...choisies, id] : choisies.filter((valeur) => valeur !== id), { shouldValidate: true });

    const onSubmit = async (valeurs: CampagnesVisibles) => {
        await envoyer<CampagnesVisibles>(url, { visible: true, campagnes: valeurs.campagnes }, { method: 'put', setError, champs: ['campagnes'], onSuccess: onClose });
    };

    return (
        <Dialog open={open} onOpenChange={(ouvert) => !ouvert && !isSubmitting && onClose()}>
            <DialogContent>
                <form onSubmit={(event) => void handleSubmit(onSubmit)(event)} noValidate className="flex min-h-0 flex-col">
                    <DialogHeader>
                        <DialogTitle>Campagnes à rendre visibles</DialogTitle>
                        <DialogDescription>Seuls les résultats des campagnes cochées seront consultables par les étudiants.</DialogDescription>
                    </DialogHeader>
                    <DialogBody>
                        {proposees.length === 0 ? (
                            <p className="text-sm text-muted">Aucun résultat publié : publiez d’abord les listes définitives d’une campagne.</p>
                        ) : (
                            <fieldset aria-describedby={errors.campagnes ? 'campagnes-visibles-erreur' : undefined}>
                                <legend className="mb-2 text-sm font-medium text-gray-800">
                                    Campagnes <span className="text-red-600">*</span>
                                </legend>
                                <div className="space-y-2">
                                    {proposees.map((campagne) => {
                                        const cochee = choisies.includes(campagne.id);

                                        return (
                                            <label
                                                key={campagne.id}
                                                className={cn(
                                                    'flex cursor-pointer items-center gap-3 rounded-brand border p-3 transition-colors',
                                                    cochee ? 'border-secondary bg-soft' : 'border-gray-200 hover:bg-surface',
                                                )}
                                            >
                                                <Checkbox
                                                    checked={cochee}
                                                    disabled={isSubmitting}
                                                    onCheckedChange={(valeur) => cocher(campagne.id, valeur === true)}
                                                    aria-label={campagne.libelle}
                                                />
                                                <span className="min-w-0">
                                                    <span className="flex flex-wrap items-center gap-2 text-sm font-medium text-gray-900">
                                                        {campagne.libelle}
                                                        {campagne.active && <Badge variant="success">Active</Badge>}
                                                    </span>
                                                </span>
                                            </label>
                                        );
                                    })}
                                </div>
                                {errors.campagnes?.message && (
                                    <p id="campagnes-visibles-erreur" role="alert" className="mt-1.5 text-xs font-medium text-red-600">
                                        {errors.campagnes.message}
                                    </p>
                                )}
                            </fieldset>
                        )}
                    </DialogBody>
                    <DialogFooter>
                        <Button type="button" variant="outline" size="sm" className="h-10" onClick={onClose} disabled={isSubmitting}>
                            Annuler
                        </Button>
                        <Button type="submit" size="sm" className="h-10" disabled={isSubmitting || choisies.length === 0}>
                            {isSubmitting ? <Loader2 className="size-4 animate-spin" aria-hidden /> : <Eye {...ICON} aria-hidden />}
                            Rendre visible
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
