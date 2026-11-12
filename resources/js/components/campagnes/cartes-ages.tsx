import type {
    AgeNiveau,
    NiveauEtudeOption,
} from "@/components/campagnes/criteres";
import { cn } from "@/lib/utils";

/** Âge maximum : une petite carte par niveau d'études (« Licence 1 · 21 ans »). */
export function CartesAges({
    ages,
    niveaux,
    className,
}: {
    ages: AgeNiveau[];
    niveaux: NiveauEtudeOption[];
    className?: string;
}) {
    if (ages.length === 0) {
        return (
            <p className={cn("text-sm text-gray-900", className)}>
                Aucune limite
            </p>
        );
    }
    return (
        <ul
            className={cn(
                "grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4",
                className,
            )}
        >
            {ages.map((ligne) => (
                <li
                    key={ligne.niveau}
                    className="rounded-brand border bg-[#F8F8F8] px-3 py-2.5 text-center"
                >
                    <p className="truncate text-sm text-muted">
                        {niveaux.find((n) => n.value === ligne.niveau)?.label ??
                            ligne.niveau}
                    </p>
                    <p className="text-base font-medium text-gray-900 tabular-nums">
                        {ligne.age} ans
                    </p>
                </li>
            ))}
        </ul>
    );
}
