import type { Errors, PageProps as InertiaPageProps } from '@inertiajs/core';

export interface AdminUser {
    name: string;
    email: string | null;
    role: string | null;
    avatar: string | null;
}

/** Entrée du menu admin, déjà filtrée côté serveur (config/admin_menu.php). */
export interface MenuLink {
    label: string;
    icon: string;
    href: string;
    active: boolean;
}

export interface MenuItem extends MenuLink {
    /** Sous-menu (clé `children` de config/admin_menu.php), vide si la rubrique n'en a pas. */
    children: MenuLink[];
}

/** Lien supplémentaire du menu de l'avatar (ex. « Mes documents » de l'espace étudiant). */
export interface UserMenuLink {
    label: string;
    icon: string;
    href: string;
}

/** Entrée du menu de l'espace étudiant (App\Services\EtudiantMenu). */
export interface EtudiantMenuItem extends MenuLink {
    /** Section du tableau de bord (`?section=`), null pour une page à part. */
    page: string | null;
    /** Section servie par une page Inertia (EtudiantMenu::SECTIONS_REACT). */
    react: boolean;
}

export interface EtudiantMenuGroup {
    label: string;
    items: EtudiantMenuItem[];
}

export interface SharedProps extends InertiaPageProps {
    errors: Errors;
    auth: {
        /** Espace étudiant : `role` contient le matricule et `avatar` est null. */
        user: AdminUser | null;
        profileUrl: string;
        logoutUrl: string;
        links: UserMenuLink[];
    };
    menu: MenuItem[];
    /** Vide hors de l'espace étudiant. */
    menuEtudiant: EtudiantMenuGroup[];
    csrfToken: string;
    sidebarOpen: boolean;
    flash: {
        success?: string | null;
        error?: string | null;
    };
}

/** Pagination Laravel (`LengthAwarePaginator` sérialisé), utilisée par `DataTable`. */
export interface PaginationLinkItem {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    first_page_url: string;
    prev_page_url: string | null;
    next_page_url: string | null;
    links: PaginationLinkItem[];
}
