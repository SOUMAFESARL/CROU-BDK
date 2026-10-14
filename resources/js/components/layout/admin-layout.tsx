import { Head, usePage } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { Fragment, type ReactNode, useEffect } from 'react';
import { toast } from 'sonner';
import { AppSidebar } from '@/components/layout/app-sidebar';
import { UserMenu } from '@/components/layout/user-menu';
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar';
import type { MenuItem, SharedProps } from '@/types/global';

/** Élément du fil d'Ariane ; sans `href` : page courante ou rubrique non cliquable. */
export type Breadcrumb = { label: string; href?: string };

type AdminLayoutProps = {
    title: string;
    /** Par défaut : Accueil › rubrique active du menu › module actif du sous-menu › titre de la page. */
    breadcrumbs?: Breadcrumb[];
    children: ReactNode;
};

/** Layout des pages admin migrées : barre latérale shadcn + barre du haut + contenu. */
export function AdminLayout({ title, breadcrumbs, children }: AdminLayoutProps) {
    const { sidebarOpen, flash, menu } = usePage<SharedProps>().props;
    const crumbs = breadcrumbs ?? defaultBreadcrumbs(menu, title);

    // Messages flash (`->with('success'|'error', …)`) affichés en toast ; `flash` change à chaque réponse serveur.
    useEffect(() => {
        if (flash.success) {
            toast.success(flash.success);
        }
        if (flash.error) {
            toast.error(flash.error);
        }
    }, [flash]);

    return (
        // État replié partagé avec les pages Blade (<x-admin-shell>) via le cookie `sidebar_state`.
        <SidebarProvider defaultOpen={sidebarOpen}>
            <Head title={title} />
            <AppSidebar />
            {/* overflow-x-clip : un contenu trop large ne doit pas faire glisser la barre du haut et la page sous la barre latérale fixe. */}
            <SidebarInset className="min-w-0 overflow-x-clip bg-surface">
                <header className="sticky top-0 z-10 flex h-16 shrink-0 items-center gap-3 border-b bg-white px-4 md:px-6">
                    {/* Bureau : replie/déplie la barre latérale ; mobile : ouvre le tiroir. */}
                    <SidebarTrigger className="-ml-1 text-muted hover:text-primary" />
                    <span className="truncate text-base font-medium text-gray-900 lg:hidden">{title}</span>
                    {/* Écrans larges : fil d'Ariane en texte fin à la place du titre. */}
                    <nav aria-label="Fil d’Ariane" className="hidden min-w-0 lg:block">
                        <ol className="flex items-center gap-1.5 text-sm font-light whitespace-nowrap text-muted">
                            {crumbs.map((crumb, index) => {
                                const last = index === crumbs.length - 1;

                                return (
                                    <Fragment key={`${crumb.label}-${index}`}>
                                        <li className="flex min-w-0 items-center">
                                            {crumb.href && !last ? (
                                                <a href={crumb.href} className="hover:text-primary">
                                                    {crumb.label}
                                                </a>
                                            ) : (
                                                <span aria-current={last ? 'page' : undefined} className={last ? 'truncate text-gray-900' : undefined}>
                                                    {crumb.label}
                                                </span>
                                            )}
                                        </li>
                                        {!last && (
                                            <li aria-hidden className="flex items-center text-gray-400">
                                                <ChevronRight size={14} strokeWidth={1.25} />
                                            </li>
                                        )}
                                    </Fragment>
                                );
                            })}
                        </ol>
                    </nav>
                    <div className="ml-auto">
                        <UserMenu />
                    </div>
                </header>
                <main className="flex-1 p-4 md:p-6">
                    {children}
                </main>
            </SidebarInset>
        </SidebarProvider>
    );
}

/** Même logique que la coque Blade (<x-admin-shell>) pour garder un fil d'Ariane identique. */
function defaultBreadcrumbs(menu: MenuItem[], title: string): Breadcrumb[] {
    const crumbs: Breadcrumb[] = [];
    const accueil = menu[0];
    const rubrique = menu.find((item) => item.active);

    if (accueil && accueil !== rubrique) {
        crumbs.push({ label: 'Accueil', href: accueil.href });
    }
    if (rubrique && rubrique.label !== title) {
        crumbs.push({ label: rubrique.label, href: rubrique.href });
    }
    const module = rubrique?.children?.find((child) => child.active);
    if (module && module.label !== title) {
        crumbs.push({ label: module.label, href: module.href });
    }
    crumbs.push({ label: title });

    return crumbs;
}
