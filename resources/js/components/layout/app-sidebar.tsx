import { usePage } from '@inertiajs/react';
import {
    BedDouble,
    BookOpen,
    CalendarCheck,
    ClipboardCheck,
    Building,
    Building2,
    ChartColumn,
    ChevronDown,
    ClipboardList,
    CreditCard,
    DoorOpen,
    FilePenLine,
    FilePlus,
    FileText,
    Flag,
    FolderOpen,
    GraduationCap,
    History,
    Houses,
    Layers,
    LayoutDashboard,
    Map as MapIcon,
    MapPin,
    Megaphone,
    Network,
    Settings,
    ShieldAlert,
    ShieldCheck,
    SlidersHorizontal,
    Toolbox,
    TriangleAlert,
    UserCog,
    Users,
    Wallet,
    Wrench,
    type LucideIcon,
} from 'lucide-react';
import { useState } from 'react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Sidebar,
    SidebarContent,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuAction,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
    SidebarRail,
    useSidebar,
} from '@/components/ui/sidebar';
import { cn } from '@/lib/utils';
import type { MenuItem, SharedProps } from '@/types/global';

/** Réglages communs des icônes Lucide de l'espace admin : trait fin, 18px. */
export const ICON = { size: 18, strokeWidth: 1.25 } as const;

/** Icônes disponibles pour config/admin_menu.php (clé `icon`, sous-menus compris) et App\Services\EtudiantMenu. */
const ICONS: Record<string, LucideIcon> = {
    'layout-dashboard': LayoutDashboard,
    users: Users,
    'building-2': Building2,
    history: History,
    settings: Settings,
    building: Building,
    toolbox: Toolbox,
    'clipboard-list': ClipboardList,
    'bed-double': BedDouble,
    wallet: Wallet,
    houses: Houses,
    megaphone: Megaphone,
    'shield-alert': ShieldAlert,
    wrench: Wrench,
    'door-open': DoorOpen,
    'chart-column': ChartColumn,
    'sliders-horizontal': SlidersHorizontal,
    'file-pen-line': FilePenLine,
    'credit-card': CreditCard,
    network: Network,
    'shield-check': ShieldCheck,
    flag: Flag,
    map: MapIcon,
    'map-pin': MapPin,
    'graduation-cap': GraduationCap,
    'book-open': BookOpen,
    layers: Layers,
    'user-cog': UserCog,
    'calendar-check': CalendarCheck,
    'file-plus': FilePlus,
    'clipboard-check': ClipboardCheck,
    'triangle-alert': TriangleAlert,
    'file-text': FileText,
    'folder-open': FolderOpen,
};

const BUTTON_CLASS = 'rounded-none border-r-3 border-transparent data-[active=true]:border-primary data-[active=true]:font-medium';

export function AppSidebar() {
    const { menu } = usePage<SharedProps>().props;

    return (
        <Sidebar collapsible="icon">
            <SidebarBrand href={menu[0]?.href ?? '#'} />

            <SidebarContent>
                <SidebarGroup>
                    <SidebarGroupLabel>Navigation</SidebarGroupLabel>
                    <SidebarGroupContent>
                        <SidebarMenu className="gap-1">
                            {menu.map((item) =>
                                item.children?.length ? (
                                    <SubMenuItem key={item.href} item={item} />
                                ) : (
                                    <SidebarMenuItem key={item.href}>
                                        <SidebarMenuButton asChild isActive={item.active} tooltip={item.label} className={BUTTON_CLASS}>
                                            {/* Les écrans non migrés sont en Blade : lien classique (rechargement complet). */}
                                            <a href={item.href} aria-current={item.active ? 'page' : undefined}>
                                                <MenuIcon name={item.icon} />
                                                <span>{item.label}</span>
                                            </a>
                                        </SidebarMenuButton>
                                    </SidebarMenuItem>
                                ),
                            )}
                        </SidebarMenu>
                    </SidebarGroupContent>
                </SidebarGroup>
            </SidebarContent>

            <SidebarRail />
        </Sidebar>
    );
}

/** Logo et nom en tête de la barre latérale (admin et espace étudiant) ; replié : logo seul. */
export function SidebarBrand({ href }: { href: string }) {
    const { logoUrl, systeme } = usePage<SharedProps>().props;

    return (
        <SidebarHeader className="border-b border-sidebar-border px-2 py-4 group-data-[collapsible=icon]:py-3">
            <div className="flex flex-col items-center">
                <a href={href} className="flex max-w-full min-w-0 flex-col items-center gap-2 rounded-md px-1 group-data-[collapsible=icon]:px-0">
                    <img src={logoUrl} alt={systeme.nom} className="size-16 shrink-0 rounded-md object-contain group-data-[collapsible=icon]:size-9" />
                    <span className="max-w-full truncate font-semibold text-primary group-data-[collapsible=icon]:hidden">{systeme.nom}</span>
                </a>
            </div>
        </SidebarHeader>
    );
}

/** Icône Lucide d'une entrée de `config/admin_menu.php` (aussi utilisée par les pages d'accueil des modules). */
export function MenuIcon({ name, size = ICON.size }: { name: string; size?: number }) {
    const Icon = ICONS[name] ?? LayoutDashboard;

    return <Icon size={size} strokeWidth={ICON.strokeWidth} />;
}

/**
 * Rubrique à sous-menu (même rendu que <x-admin-shell>) : dépliée quand elle est active,
 * chevron pour la déplier/replier ; en mode replié, menu flottant à droite de l'icône.
 */
function SubMenuItem({ item }: { item: MenuItem }) {
    const { state, isMobile } = useSidebar();
    const [open, setOpen] = useState(item.active);
    const activeChild = item.children.some((child) => child.active);

    if (state === 'collapsed' && !isMobile) {
        return (
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <SidebarMenuButton isActive={item.active} tooltip={item.label} className={BUTTON_CLASS}>
                            <MenuIcon name={item.icon} />
                            <span>{item.label}</span>
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent side="right" align="start" className="min-w-60">
                        <DropdownMenuLabel className="text-xs font-medium text-muted">
                            <a href={item.href} className="hover:text-primary">
                                {item.label}
                            </a>
                        </DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        {item.children.map((child) => (
                            <DropdownMenuItem key={child.href} asChild className={cn(child.active && 'bg-soft font-medium text-primary')}>
                                <a href={child.href} aria-current={child.active ? 'page' : undefined}>
                                    <MenuIcon name={child.icon} size={16} />
                                    <span>{child.label}</span>
                                </a>
                            </DropdownMenuItem>
                        ))}
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        );
    }

    return (
        <SidebarMenuItem>
            <SidebarMenuButton
                asChild
                isActive={item.active}
                tooltip={item.label}
                // Un module actif porte la mise en évidence : la rubrique garde seulement la couleur.
                className={cn(BUTTON_CLASS, 'pr-9', activeChild && 'data-[active=true]:border-transparent data-[active=true]:bg-transparent data-[active=true]:hover:bg-sidebar-accent')}
            >
                <a href={item.href} aria-current={item.active && !activeChild ? 'page' : undefined}>
                    <MenuIcon name={item.icon} />
                    <span>{item.label}</span>
                </a>
            </SidebarMenuButton>
            <SidebarMenuAction
                onClick={() => setOpen((value) => !value)}
                aria-expanded={open}
                aria-label={`Afficher ou masquer les modules de ${item.label}`}
                className="top-2 right-2 size-6"
            >
                <ChevronDown className={cn('transition-transform duration-250 ease-out motion-reduce:transition-none', open && 'rotate-180')} strokeWidth={ICON.strokeWidth} />
            </SidebarMenuAction>
            {/* Ouverture/fermeture fluide : ligne de grille 0fr ↔ 1fr + fondu ; `inert` retire les liens masqués du parcours clavier. */}
            <div
                inert={!open}
                className={cn(
                    'grid transition-[grid-template-rows,opacity,margin] duration-250 ease-out motion-reduce:transition-none',
                    open ? 'mt-0.5 mb-1.5 grid-rows-[1fr] opacity-100' : 'grid-rows-[0fr] opacity-0',
                )}
            >
                <SidebarMenuSub className="mr-0 ml-4 min-h-0 gap-0.5 overflow-hidden py-0 pr-0 pl-2">
                    {item.children.map((child) => (
                        <SidebarMenuSubItem key={child.href}>
                            <SidebarMenuSubButton asChild isActive={child.active} className="h-8 text-muted data-[active=true]:bg-soft data-[active=true]:font-medium data-[active=true]:text-primary hover:bg-soft hover:text-primary focus-visible:ring-inset [&>svg]:text-current">
                                <a href={child.href} aria-current={child.active ? 'page' : undefined}>
                                    <MenuIcon name={child.icon} size={16} />
                                    <span>{child.label}</span>
                                </a>
                            </SidebarMenuSubButton>
                        </SidebarMenuSubItem>
                    ))}
                </SidebarMenuSub>
            </div>
        </SidebarMenuItem>
    );
}
