import { Head, usePage } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { type ReactNode, useEffect } from 'react';
import { toast } from 'sonner';
import { MenuIcon, SidebarBrand } from '@/components/layout/app-sidebar';
import { UserMenu } from '@/components/layout/user-menu';
import {
    Sidebar,
    SidebarContent,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarInset,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarProvider,
    SidebarRail,
    SidebarTrigger,
} from '@/components/ui/sidebar';
import type { SharedProps } from '@/types/global';

/** Entrée sélectionnée (et survol) en orange #FAAB04, comme <x-etudiant-shell>. */
const BUTTON_CLASS =
    'rounded-none border-r-3 border-transparent hover:bg-[#FFF5DD] hover:text-[#A86400] data-[active=true]:border-accent data-[active=true]:bg-[#FFF5DD] data-[active=true]:font-medium data-[active=true]:text-[#A86400]';

type EtudiantLayoutProps = {
    title: string;
    children: ReactNode;
};

/**
 * Mise en page des pages React de l'espace étudiant : même barre latérale et même barre du haut
 * que la coque Blade <x-etudiant-shell> (menu App\Services\EtudiantMenu). Pensée d'abord pour le smartphone.
 */
export function EtudiantLayout({ title, children }: EtudiantLayoutProps) {
    const { sidebarOpen, flash, menuEtudiant } = usePage<SharedProps>().props;
    const accueil = menuEtudiant[0]?.items[0];

    useEffect(() => {
        if (flash.success) toast.success(flash.success);
        if (flash.error) toast.error(flash.error);
    }, [flash]);

    return (
        <SidebarProvider defaultOpen={sidebarOpen}>
            <Head title={title} />
            <Sidebar collapsible="icon">
                <SidebarBrand href={accueil?.href ?? '#'} />
                <SidebarContent>
                    {menuEtudiant.map((groupe) => (
                        <SidebarGroup key={groupe.label}>
                            <SidebarGroupLabel>{groupe.label}</SidebarGroupLabel>
                            <SidebarGroupContent>
                                <SidebarMenu className="gap-1">
                                    {groupe.items.map((item) => (
                                        <SidebarMenuItem key={item.href}>
                                            <SidebarMenuButton asChild isActive={item.active} tooltip={item.label} className={BUTTON_CLASS}>
                                                {/* Le tableau de bord et les autres pages non migrées sont en Blade : lien classique. */}
                                                <a href={item.href} aria-current={item.active ? 'page' : undefined}>
                                                    <MenuIcon name={item.icon} />
                                                    <span>{item.label}</span>
                                                </a>
                                            </SidebarMenuButton>
                                        </SidebarMenuItem>
                                    ))}
                                </SidebarMenu>
                            </SidebarGroupContent>
                        </SidebarGroup>
                    ))}
                </SidebarContent>
                <SidebarRail />
            </Sidebar>

            <SidebarInset className="min-w-0 overflow-x-clip bg-surface">
                <header className="sticky top-0 z-10 flex h-16 shrink-0 items-center gap-3 border-b bg-white px-4 md:px-6">
                    <SidebarTrigger className="-ml-1 text-muted hover:text-primary" />
                    <span className="truncate text-base font-medium text-gray-900 lg:hidden">{title}</span>
                    {/* Écrans larges : Accueil › page courante. */}
                    <nav aria-label="Fil d’Ariane" className="hidden min-w-0 lg:block">
                        <ol className="flex items-center gap-1.5 text-sm font-light whitespace-nowrap text-muted">
                            {accueil && (
                                <>
                                    <li>
                                        <a href={accueil.href} className="hover:text-primary">
                                            Accueil
                                        </a>
                                    </li>
                                    <li aria-hidden className="flex items-center text-gray-400">
                                        <ChevronRight size={14} strokeWidth={1.25} />
                                    </li>
                                </>
                            )}
                            <li>
                                <span aria-current="page" className="truncate text-gray-900">
                                    {title}
                                </span>
                            </li>
                        </ol>
                    </nav>
                    <div className="ml-auto">
                        <UserMenu />
                    </div>
                </header>
                <main className="flex-1 p-4 md:p-6">{children}</main>
            </SidebarInset>
        </SidebarProvider>
    );
}
