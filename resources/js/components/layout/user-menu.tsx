import { usePage } from '@inertiajs/react';
import { ChevronDown, LogOut, UserRound } from 'lucide-react';
import { useRef } from 'react';
import { ICON } from '@/components/layout/app-sidebar';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { SharedProps } from '@/types/global';

function initiales(nom: string): string {
    return nom
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((mot) => mot[0]?.toUpperCase())
        .join('');
}

function csrfToken(): string {
    return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
}

/** Avatar circulaire et nom de l'admin connecté (barre du haut), avec profil et déconnexion. */
export function UserMenu() {
    const { auth } = usePage<SharedProps>().props;
    const logoutForm = useRef<HTMLFormElement>(null);
    const user = auth.user;

    if (!user) {
        return null;
    }

    return (
        <>
            <DropdownMenu>
                <DropdownMenuTrigger className="flex cursor-pointer items-center gap-2.5 rounded-md py-1 pr-1.5 pl-1 text-left outline-none hover:bg-soft focus-visible:ring-2 focus-visible:ring-secondary data-[state=open]:bg-soft">
                    <Avatar className="size-9 rounded-full">
                        {user.avatar && <AvatarImage src={user.avatar} alt={user.name} className="object-cover" />}
                        <AvatarFallback className="rounded-full bg-primary text-xs font-semibold text-white">{initiales(user.name)}</AvatarFallback>
                    </Avatar>
                    <span className="hidden min-w-0 leading-tight sm:grid">
                        <span className="max-w-48 truncate text-sm font-medium text-gray-900">{user.name}</span>
                        {user.role && <span className="max-w-48 truncate text-xs text-muted">{user.role}</span>}
                    </span>
                    <ChevronDown {...ICON} className="hidden text-muted sm:block" />
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="min-w-56">
                    <DropdownMenuLabel className="font-normal">
                        <span className="block truncate font-medium text-gray-900">{user.name}</span>
                        <span className="block truncate text-xs text-muted">{user.email}</span>
                    </DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem asChild>
                        <a href={auth.profileUrl}>
                            <UserRound {...ICON} className="size-[18px]" />
                            Mon profil
                        </a>
                    </DropdownMenuItem>
                    <DropdownMenuItem variant="destructive" onSelect={() => logoutForm.current?.submit()}>
                        <LogOut {...ICON} className="size-[18px]" />
                        Déconnexion
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
            {/* Déconnexion en POST classique (route admin.logout inchangée). */}
            <form ref={logoutForm} method="POST" action={auth.logoutUrl} hidden>
                <input type="hidden" name="_token" value={csrfToken()} />
            </form>
        </>
    );
}
