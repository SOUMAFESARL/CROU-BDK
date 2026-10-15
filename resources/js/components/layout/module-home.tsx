import { ArrowUpRight } from 'lucide-react';
import { AdminLayout } from '@/components/layout/admin-layout';
import { MenuIcon } from '@/components/layout/app-sidebar';
import { PageHeader } from '@/components/ui/page-header';
import { cn } from '@/lib/utils';

/** Module d'un sous-menu de `config/admin_menu.php` (`AdminMenu::childrenOf()`, déjà filtré par droits). */
export type ModuleLink = {
    label: string;
    icon: string;
    href: string;
    color: 'green' | 'amber' | 'red' | 'purple' | null;
    description: string | null;
};

const COULEURS = {
    green: 'bg-soft text-primary',
    amber: 'bg-[#FFF6E5] text-[#A15C00]',
    red: 'bg-[#FFF0EF] text-[#C2362F]',
    purple: 'bg-[#F1EDFF] text-[#5B3FB5]',
};

/** Page d'accueil d'une rubrique : une carte par module accessible, dans l'ordre du menu. */
export function ModuleHome({ title, description, modules }: { title: string; description?: string; modules: ModuleLink[] }) {
    return (
        <AdminLayout title={title}>
            <PageHeader title={title} description={description} />
            {modules.length === 0 ? (
                <p className="rounded-brand border bg-white p-6 text-sm text-muted">Aucun module n’est accessible avec vos droits.</p>
            ) : (
                <nav aria-label={`Modules de ${title}`} className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    {modules.map((module) => (
                        // Liens classiques : une partie des modules est encore servie en Blade.
                        <a
                            key={module.href}
                            href={module.href}
                            className="group flex items-center gap-3 rounded-brand border bg-white p-3.5 shadow-[0_7px_24px_rgba(30,45,75,0.045)] transition hover:-translate-y-0.5 hover:shadow-[0_10px_25px_rgba(30,45,75,0.09)] focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none"
                        >
                            <span className={cn('flex size-9 shrink-0 items-center justify-center rounded-brand', COULEURS[module.color ?? 'green'])} aria-hidden>
                                <MenuIcon name={module.icon} />
                            </span>
                            <span className="flex min-w-0 flex-col">
                                {module.description && <small className="truncate text-sm text-muted">{module.description}</small>}
                                <strong className="truncate text-sm font-medium text-gray-900">{module.label}</strong>
                            </span>
                            <ArrowUpRight className="ml-auto size-4.5 shrink-0 text-gray-400 transition-colors group-hover:text-primary" strokeWidth={1.25} aria-hidden />
                        </a>
                    ))}
                </nav>
            )}
        </AdminLayout>
    );
}
