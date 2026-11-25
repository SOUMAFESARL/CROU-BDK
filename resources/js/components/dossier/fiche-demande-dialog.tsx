import { Download, Loader2, Printer } from 'lucide-react';
import { useRef, useState } from 'react';
import { ICON } from '@/components/layout/app-sidebar';
import { Button, buttonVariants } from '@/components/ui/button';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { cn } from '@/lib/utils';

type Props = {
    /** PDF affiché dans la page ; `?telecharger=1` l'enregistre. */
    url: string;
    description: string;
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

/** Aperçu de la fiche de demande (PDF) : impression directe, ou téléchargement. */
export function FicheDemandeDialog({ url, description, open, onOpenChange }: Props) {
    const cadre = useRef<HTMLIFrameElement>(null);
    const [charge, setCharge] = useState(false);

    const changer = (valeur: boolean) => {
        if (!valeur) setCharge(false);
        onOpenChange(valeur);
    };

    const imprimer = () => {
        try {
            const fenetre = cadre.current?.contentWindow;
            if (!fenetre) throw new Error('Aperçu indisponible');
            fenetre.focus();
            fenetre.print();
        } catch {
            // Lecteur PDF sans impression intégrée : la fiche s'ouvre dans un onglet, d'où elle peut être imprimée.
            window.open(url, '_blank', 'noopener');
        }
    };

    return (
        <Dialog open={open} onOpenChange={changer}>
            <DialogContent className="h-[calc(100dvh-2rem)] max-w-4xl">
                <DialogHeader>
                    <DialogTitle>Fiche de demande</DialogTitle>
                    <DialogDescription>{description}</DialogDescription>
                </DialogHeader>
                <DialogBody className="relative bg-surface p-0">
                    {!charge && (
                        <p role="status" className="absolute inset-0 flex items-center justify-center gap-2 text-sm text-muted">
                            <Loader2 {...ICON} className="animate-spin" aria-hidden /> Préparation de la fiche…
                        </p>
                    )}
                    <iframe ref={cadre} src={url} title="Aperçu de la fiche de demande" onLoad={() => setCharge(true)} className="relative size-full border-0" />
                </DialogBody>
                <DialogFooter>
                    <a href={`${url}?telecharger=1`} className={cn(buttonVariants({ variant: 'outline' }), 'font-medium')}>
                        <Download {...ICON} aria-hidden /> Télécharger
                    </a>
                    <Button onClick={imprimer} disabled={!charge} className="font-medium">
                        <Printer {...ICON} aria-hidden /> Imprimer
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
