import { CircleAlert, CircleCheck } from 'lucide-react';
import { Toaster as Sonner } from 'sonner';
import { useIsMobile } from '@/hooks/use-mobile';

/**
 * Toasts de l'espace admin (Sonner) : en haut à droite sur grand écran, centrés en haut sur mobile.
 * Monté une seule fois à la racine (`admin.tsx`) pour survivre aux changements de page Inertia.
 */
export function Toaster() {
    const isMobile = useIsMobile();

    return (
        <Sonner
            position={isMobile ? 'top-center' : 'top-right'}
            duration={5000}
            closeButton
            icons={{
                success: <CircleCheck className="size-5 text-primary" aria-hidden />,
                error: <CircleAlert className="size-5 text-red-600" aria-hidden />,
            }}
            toastOptions={{
                classNames: {
                    toast: '!rounded-brand !border !bg-white !font-sans !text-sm !shadow-lg',
                    success: '!border-secondary/40 !text-primary',
                    error: '!border-red-200 !text-red-700',
                    closeButton: '!border-gray-200 !bg-white !text-muted hover:!text-gray-900',
                },
            }}
        />
    );
}
