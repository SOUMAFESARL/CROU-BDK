import { ChevronRight, GraduationCap, ShieldCheck } from 'lucide-react';
import { AuthLayout } from '@/components/auth/auth-layout';

type ChoixProps = {
    adminLoginUrl: string;
    etudiantLoginUrl: string;
    logoUrl: string;
    backgroundUrl: string;
    year: number;
};

/** Page d'accueil : choix de l'espace de connexion (administration ou étudiant). */
export default function Choix({ adminLoginUrl, etudiantLoginUrl, logoUrl, backgroundUrl, year }: ChoixProps) {
    const espaces = [
        { href: etudiantLoginUrl, icon: GraduationCap, label: 'Espace étudiant', description: 'Demande de logement, contrat, paiements…' },
        { href: adminLoginUrl, icon: ShieldCheck, label: 'Espace administration', description: 'Gestion des services du CROU.' },
    ];

    return (
        <AuthLayout title="Connexion" subtitle="Choisissez votre espace de connexion." logoUrl={logoUrl} backgroundUrl={backgroundUrl} year={year}>
            <div className="mt-6 space-y-3">
                {espaces.map(({ href, icon: Icon, label, description }) => (
                    <a
                        key={href}
                        href={href}
                        className="group flex items-center gap-4 rounded-brand border border-gray-200 bg-white p-4 transition hover:border-secondary hover:bg-soft focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none"
                    >
                        <span className="flex size-11 shrink-0 items-center justify-center rounded-full bg-soft text-primary group-hover:bg-white">
                            <Icon className="size-5" />
                        </span>
                        <span className="flex-1 text-left">
                            <span className="block font-semibold text-gray-900">{label}</span>
                            <span className="block text-xs text-muted">{description}</span>
                        </span>
                        <ChevronRight className="size-4 text-muted transition group-hover:translate-x-0.5 group-hover:text-primary" />
                    </a>
                ))}
            </div>
        </AuthLayout>
    );
}
