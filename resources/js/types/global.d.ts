import type { Errors, PageProps as InertiaPageProps } from '@inertiajs/core';

export interface AdminUser {
    name: string;
    email: string;
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

export interface SharedProps extends InertiaPageProps {
    errors: Errors;
    auth: {
        user: AdminUser | null;
        profileUrl: string;
        logoutUrl: string;
    };
    menu: MenuItem[];
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
