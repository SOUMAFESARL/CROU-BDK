import { Loader2 } from 'lucide-react';
import type { ReactNode } from 'react';
import {
    AlertDialog,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type ConfirmDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    description?: ReactNode;
    /** Contenu supplémentaire (champ motif…). */
    children?: ReactNode;
    confirmLabel: string;
    confirmIcon?: ReactNode;
    onConfirm: () => void;
    processing?: boolean;
    danger?: boolean;
    confirmDisabled?: boolean;
};

/** Confirmation d'une action sensible ou irréversible (suppression, validation, attribution automatique…). */
export function ConfirmDialog({
    open,
    onOpenChange,
    title,
    description,
    children,
    confirmLabel,
    confirmIcon,
    onConfirm,
    processing = false,
    danger = false,
    confirmDisabled = false,
}: ConfirmDialogProps) {
    return (
        <AlertDialog open={open} onOpenChange={(ouvert) => !processing && onOpenChange(ouvert)}>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>{title}</AlertDialogTitle>
                    {description && <AlertDialogDescription asChild><div>{description}</div></AlertDialogDescription>}
                </AlertDialogHeader>
                {children}
                <AlertDialogFooter>
                    <AlertDialogCancel asChild>
                        <Button variant="outline" size="sm" className="h-10" disabled={processing}>
                            Annuler
                        </Button>
                    </AlertDialogCancel>
                    <Button
                        size="sm"
                        className={cn('h-10', danger && 'bg-red-600 hover:bg-red-700 focus-visible:ring-red-300')}
                        disabled={processing || confirmDisabled}
                        onClick={onConfirm}
                    >
                        {processing ? <Loader2 className="size-4 animate-spin" aria-hidden /> : confirmIcon}
                        {confirmLabel}
                    </Button>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
