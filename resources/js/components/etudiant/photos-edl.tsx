import { Camera, ImagePlus, Loader2, X } from "lucide-react";
import { useRef, useState } from "react";
import { toast } from "sonner";
import { ICON } from "@/components/layout/app-sidebar";
import { cn } from "@/lib/utils";

const TYPES = ["image/jpeg", "image/png", "image/webp"];

/** Réduit la photo (1600 px max, JPEG 80 %) avant l'envoi, comme l'ancien script edl-photos.js : EdlPhotoService refuse plus de 2 Mo. */
async function preparer(fichier: File): Promise<string> {
    if (!TYPES.includes(fichier.type) || fichier.size > 20 * 1024 * 1024) {
        throw new Error("Choisissez une photo JPEG, PNG ou WebP de moins de 20 Mo.");
    }
    const url = URL.createObjectURL(fichier);
    try {
        const image = new Image();
        image.src = url;
        await image.decode();
        const ratio = Math.min(1, 1600 / Math.max(image.width, image.height));
        const canvas = document.createElement("canvas");
        canvas.width = Math.max(1, Math.round(image.width * ratio));
        canvas.height = Math.max(1, Math.round(image.height * ratio));
        canvas.getContext("2d")?.drawImage(image, 0, 0, canvas.width, canvas.height);
        return canvas.toDataURL("image/jpeg", 0.8);
    } finally {
        URL.revokeObjectURL(url);
    }
}

type Props = {
    /** Chemins déjà enregistrés ou images `data:` à téléverser. */
    valeurs: string[];
    onChange: (valeurs: string[]) => void;
    max: number;
    /** URL d'affichage d'un chemin enregistré. */
    urlDe: (valeur: string) => string;
    libelle: string;
    /** Signale qu'une préparation est en cours (l'envoi attend). */
    onPreparation?: (enCours: boolean) => void;
    compact?: boolean;
};

/** Photos facultatives d'une pièce ou d'un élément : prise avec l'appareil photo ou choix d'un fichier, aperçu et retrait. */
export function PhotosEdl({ valeurs, onChange, max, urlDe, libelle, onPreparation, compact }: Props) {
    const [enCours, setEnCours] = useState(false);
    const camera = useRef<HTMLInputElement>(null);
    const fichiers = useRef<HTMLInputElement>(null);
    const plein = valeurs.length >= max;
    const unique = max === 1;

    const ajouter = async (liste: FileList | null) => {
        if (!liste?.length) return;
        setEnCours(true);
        onPreparation?.(true);
        try {
            const nouvelles: string[] = [];
            for (const fichier of Array.from(liste)) {
                if (!unique && valeurs.length + nouvelles.length >= max) {
                    toast.error(`${max} photos maximum.`);
                    break;
                }
                nouvelles.push(await preparer(fichier));
            }
            onChange(unique ? nouvelles.slice(-1) : [...valeurs, ...nouvelles]);
        } catch (erreur) {
            toast.error(erreur instanceof Error ? erreur.message : "Impossible de lire cette photo.");
        } finally {
            setEnCours(false);
            onPreparation?.(false);
            if (camera.current) camera.current.value = "";
            if (fichiers.current) fichiers.current.value = "";
        }
    };

    const bouton = cn(
        "inline-flex cursor-pointer items-center gap-1.5 rounded-brand border border-dashed border-gray-300 bg-white px-3 text-sm text-muted transition-colors hover:border-primary hover:text-primary has-focus-visible:ring-2 has-focus-visible:ring-secondary",
        compact ? "h-9" : "h-10",
    );

    return (
        <div className="flex flex-wrap items-center gap-2" aria-label={libelle} role="group">
            {valeurs.map((valeur, index) => (
                <figure key={`${index}-${valeur.slice(-24)}`} className="relative size-16 overflow-hidden rounded-brand border">
                    <a href={urlDe(valeur)} target="_blank" rel="noopener noreferrer">
                        <img src={urlDe(valeur)} alt={`${libelle} ${index + 1}`} className="size-full object-cover" />
                    </a>
                    <button
                        type="button"
                        onClick={() => onChange(valeurs.filter((_, i) => i !== index))}
                        aria-label={`Retirer la photo ${index + 1}`}
                        className="absolute top-0.5 right-0.5 grid size-6 cursor-pointer place-items-center rounded-sm bg-black/60 text-white hover:bg-black/80"
                    >
                        <X size={14} strokeWidth={1.5} />
                    </button>
                </figure>
            ))}
            {enCours && (
                <span className="inline-flex items-center gap-1.5 text-xs text-muted">
                    <Loader2 {...ICON} size={16} className="animate-spin" />
                    Préparation…
                </span>
            )}
            {(!plein || unique) && !enCours && (
                <>
                    <label className={bouton}>
                        <Camera {...ICON} size={16} />
                        {compact ? <span className="sr-only">Prendre une photo</span> : "Prendre une photo"}
                        <input
                            ref={camera}
                            type="file"
                            accept={TYPES.join(",")}
                            capture="environment"
                            className="sr-only"
                            onChange={(e) => ajouter(e.target.files)}
                        />
                    </label>
                    <label className={bouton}>
                        <ImagePlus {...ICON} size={16} />
                        {compact ? (
                            <span className="sr-only">{unique && valeurs.length ? "Remplacer la photo" : "Choisir une photo"}</span>
                        ) : unique ? (
                            "Choisir une photo"
                        ) : (
                            "Ajouter des photos"
                        )}
                        <input
                            ref={fichiers}
                            type="file"
                            accept={TYPES.join(",")}
                            multiple={!unique}
                            className="sr-only"
                            onChange={(e) => ajouter(e.target.files)}
                        />
                    </label>
                </>
            )}
        </div>
    );
}

/** Vignettes en lecture seule. */
export function VignettesEdl({ photos, libelle }: { photos: { url: string | null }[]; libelle: string }) {
    const visibles = photos.filter((p): p is { url: string } => !!p.url);
    if (visibles.length === 0) return null;

    return (
        <div className="flex flex-wrap gap-2">
            {visibles.map((photo, index) => (
                <a
                    key={photo.url}
                    href={photo.url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="size-16 overflow-hidden rounded-brand border focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none"
                >
                    <img src={photo.url} alt={`${libelle} ${index + 1}`} className="size-full object-cover" />
                </a>
            ))}
        </div>
    );
}
