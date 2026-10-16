import { Eraser } from "lucide-react";
import { type PointerEvent, useCallback, useEffect, useRef } from "react";
import { ICON } from "@/components/layout/app-sidebar";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";

type Point = { x: number; y: number };

type Props = {
    id: string;
    /** Image `data:image/png` de la signature, null tant que rien n'est dessiné. */
    value: string | null;
    onChange: (value: string | null) => void;
    invalid?: boolean;
    disabled?: boolean;
    "aria-describedby"?: string;
};

/** Cadre de signature (souris, doigt ou stylet) : renvoie l'image PNG à chaque trait terminé. */
export function SignaturePad({ id, value, onChange, invalid, disabled, ...props }: Props) {
    const canvas = useRef<HTMLCanvasElement>(null);
    const traits = useRef<Point[][]>([]);
    const enCours = useRef<Point[] | null>(null);

    const dessiner = useCallback(() => {
        const c = canvas.current;
        const ctx = c?.getContext("2d");
        if (!c || !ctx) return;
        const ratio = window.devicePixelRatio || 1;
        ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
        ctx.clearRect(0, 0, c.width, c.height);
        ctx.lineWidth = 2;
        ctx.lineCap = "round";
        ctx.lineJoin = "round";
        ctx.strokeStyle = "#1F2328";
        traits.current.forEach((trait) => {
            if (trait.length === 0) return;
            ctx.beginPath();
            ctx.moveTo(trait[0].x, trait[0].y);
            if (trait.length === 1) ctx.lineTo(trait[0].x + 0.1, trait[0].y); // un simple appui laisse un point
            trait.slice(1).forEach((p) => ctx.lineTo(p.x, p.y));
            ctx.stroke();
        });
    }, []);

    // Taille réelle du cadre × densité de l'écran, recalculée au redimensionnement (les traits sont conservés).
    useEffect(() => {
        const c = canvas.current;
        if (!c) return;
        const ajuster = () => {
            const ratio = window.devicePixelRatio || 1;
            const { width, height } = c.getBoundingClientRect();
            c.width = Math.max(1, Math.round(width * ratio));
            c.height = Math.max(1, Math.round(height * ratio));
            dessiner();
        };
        ajuster();
        const observateur = new ResizeObserver(ajuster);
        observateur.observe(c);
        return () => observateur.disconnect();
    }, [dessiner]);

    // Effacement depuis le formulaire (reset).
    useEffect(() => {
        if (value === null && traits.current.length > 0 && !enCours.current) {
            traits.current = [];
            dessiner();
        }
    }, [value, dessiner]);

    const position = (e: PointerEvent<HTMLCanvasElement>): Point => {
        const rect = e.currentTarget.getBoundingClientRect();
        return { x: e.clientX - rect.left, y: e.clientY - rect.top };
    };

    const commencer = (e: PointerEvent<HTMLCanvasElement>) => {
        if (disabled) return;
        e.currentTarget.setPointerCapture(e.pointerId);
        enCours.current = [position(e)];
        traits.current.push(enCours.current);
        dessiner();
    };

    const continuer = (e: PointerEvent<HTMLCanvasElement>) => {
        if (!enCours.current) return;
        enCours.current.push(position(e));
        dessiner();
    };

    const terminer = () => {
        if (!enCours.current) return;
        enCours.current = null;
        onChange(canvas.current?.toDataURL("image/png") ?? null);
    };

    const effacer = () => {
        traits.current = [];
        dessiner();
        onChange(null);
    };

    return (
        <div className="flex flex-col gap-2">
            <canvas
                id={id}
                ref={canvas}
                role="img"
                aria-label="Cadre de signature"
                aria-invalid={invalid || undefined}
                aria-describedby={props["aria-describedby"]}
                onPointerDown={commencer}
                onPointerMove={continuer}
                onPointerUp={terminer}
                onPointerCancel={terminer}
                className={cn(
                    "h-40 w-full touch-none rounded-brand border border-dashed bg-surface",
                    disabled ? "cursor-not-allowed opacity-60" : "cursor-crosshair",
                    invalid ? "border-red-500" : "border-gray-300",
                )}
            />
            <div className="flex items-center justify-between gap-2">
                <small className="text-xs text-muted">
                    {value ? "Signature prête." : "Signez avec la souris, le doigt ou un stylet."}
                </small>
                <Button type="button" variant="ghost" size="sm" onClick={effacer} disabled={disabled || !value}>
                    <Eraser {...ICON} size={16} />
                    Effacer
                </Button>
            </div>
        </div>
    );
}
