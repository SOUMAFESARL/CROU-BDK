import { Link, router } from '@inertiajs/react';
import { ArrowLeft, CalendarClock, KeyRound, Mail, Pencil, Phone, Power, PowerOff, ShieldCheck, User, type LucideIcon } from 'lucide-react';
import { useState, type ReactNode } from 'react';
import { AdminLayout } from '@/components/layout/admin-layout';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button, buttonVariants } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type Statut = 'active' | 'inactive';

type UsersShowProps = {
    user: {
        name: string;
        civilite: string | null;
        matricule: string | null;
        role: string | null;
        email: string | null;
        telephone: string | null;
        statut: Statut;
        createdAt: string | null;
        updatedAt: string | null;
    };
    showPersonal: boolean;
    urls: {
        index: string | null;
        edit: string | null;
        status: string | null;
    };
};

const initiales = (name: string) =>
    name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((word) => Array.from(word)[0] ?? '')
        .join('')
        .toUpperCase() || 'U';

/** « 0700000000 » → « 07 00 00 00 00 ». */
const formatTelephone = (value: string) => (value.replace(/\D/g, '').match(/.{1,2}/g) ?? [value]).join(' ');

const formatDate = (value: string | null) =>
    value ? new Intl.DateTimeFormat('fr-FR', { dateStyle: 'long', timeStyle: 'short' }).format(new Date(value)) : null;

function DetailItem({ icon: Icon, label, children }: { icon: LucideIcon; label: string; children: ReactNode }) {
    return (
        <div className="flex items-start gap-3 rounded-brand border bg-gray-50/60 p-4">
            <span className="flex size-9 shrink-0 items-center justify-center rounded-brand bg-white text-primary ring-1 ring-gray-200" aria-hidden>
                <Icon className="size-[18px]" strokeWidth={1.25} />
            </span>
            <div className="min-w-0">
                <dt className="text-xs text-muted">{label}</dt>
                <dd className="mt-0.5 text-sm font-medium break-words text-gray-900">{children}</dd>
            </div>
        </div>
    );
}

const empty = <span className="font-normal text-muted">Non renseigné</span>;

export default function UsersShow({ user, showPersonal, urls }: UsersShowProps) {
    const [confirming, setConfirming] = useState(false);
    const [processing, setProcessing] = useState(false);
    const active = user.statut === 'active';

    function toggleStatus() {
        if (!urls.status) return;
        router.put(
            urls.status,
            { statut: active ? 'inactive' : 'active' },
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => {
                    setProcessing(false);
                    setConfirming(false);
                },
            },
        );
    }

    return (
        <AdminLayout title="Fiche utilisateur">
            <div className="mb-6 flex flex-col gap-1">
                <h1 className="page-title text-xl font-medium text-gray-900">Fiche utilisateur</h1>
                <p className="text-sm text-muted">Informations du compte d'accès à l'espace d'administration.</p>
            </div>

            <div className="mx-auto w-full max-w-4xl">
                <div className="rounded-brand border bg-white">
                    <div className="flex flex-col gap-4 border-b p-4 sm:flex-row sm:items-center sm:justify-between md:p-6">
                        <div className="flex min-w-0 items-center gap-4">
                            <span className="flex size-14 shrink-0 items-center justify-center rounded-full bg-soft text-lg font-semibold text-primary" aria-hidden>
                                {initiales(user.name)}
                            </span>
                            <div className="min-w-0">
                                <p className="truncate text-lg font-medium text-gray-900">{user.name}</p>
                                <div className="mt-1 flex flex-wrap items-center gap-2">
                                    <span className="inline-flex items-center gap-1.5 text-sm text-muted">
                                        <ShieldCheck className="size-4" strokeWidth={1.25} aria-hidden />
                                        {user.role ?? 'Rôle non renseigné'}
                                    </span>
                                    {active ? (
                                        <Badge variant="success">
                                            <span className="size-1.5 rounded-full bg-primary" aria-hidden />
                                            Actif
                                        </Badge>
                                    ) : (
                                        <Badge variant="neutral">
                                            <span className="size-1.5 rounded-full bg-gray-400" aria-hidden />
                                            Inactif
                                        </Badge>
                                    )}
                                </div>
                            </div>
                        </div>

                        <div className="flex flex-col gap-2 sm:flex-row sm:shrink-0">
                            {urls.status && (
                                <Button
                                    type="button"
                                    variant="outline"
                                    className={cn('h-10', active && 'text-red-700 hover:bg-red-50 hover:text-red-700')}
                                    onClick={() => setConfirming(true)}
                                >
                                    {active ? <PowerOff className="size-4" aria-hidden /> : <Power className="size-4" aria-hidden />}
                                    {active ? 'Désactiver' : 'Activer'}
                                </Button>
                            )}
                            {urls.edit && (
                                <Link href={urls.edit} className={cn(buttonVariants(), 'h-10')}>
                                    <Pencil className="size-4" aria-hidden />
                                    Modifier
                                </Link>
                            )}
                        </div>
                    </div>

                    <div className="p-4 md:p-6">
                        <h2 className="mb-4 text-sm font-semibold text-gray-900">Informations du compte</h2>
                        <dl className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            {showPersonal && <DetailItem icon={User} label="Civilité">{user.civilite ?? empty}</DetailItem>}
                            <DetailItem icon={User} label="Nom complet">
                                {user.name}
                            </DetailItem>
                            {user.matricule && (
                                <DetailItem icon={User} label="Matricule">
                                    {user.matricule}
                                </DetailItem>
                            )}
                            <DetailItem icon={ShieldCheck} label="Rôle">
                                {user.role ?? empty}
                            </DetailItem>
                            {showPersonal && (
                                <>
                                    <DetailItem icon={Mail} label="Adresse e-mail">
                                        {user.email ?? empty}
                                    </DetailItem>
                                    <DetailItem icon={Phone} label="Téléphone">
                                        {user.telephone ? <span className="tabular-nums">+225 {formatTelephone(user.telephone)}</span> : empty}
                                    </DetailItem>
                                </>
                            )}
                            <DetailItem icon={KeyRound} label="Mot de passe">
                                <span className="tracking-widest" aria-label="Mot de passe masqué">
                                    ••••••••
                                </span>
                            </DetailItem>
                            {user.createdAt && (
                                <DetailItem icon={CalendarClock} label="Créé le">
                                    {formatDate(user.createdAt)}
                                </DetailItem>
                            )}
                            {user.updatedAt && (
                                <DetailItem icon={CalendarClock} label="Dernière modification">
                                    {formatDate(user.updatedAt)}
                                </DetailItem>
                            )}
                        </dl>
                        {urls.edit && <p className="mt-4 text-xs text-muted">Pour changer le mot de passe, utilisez le bouton « Modifier ».</p>}
                    </div>

                    {urls.index && (
                        <div className="flex border-t px-4 py-4 md:px-6">
                            <Link href={urls.index} className={cn(buttonVariants({ variant: 'outline' }), 'h-10 w-full sm:w-auto')}>
                                <ArrowLeft className="size-4" aria-hidden />
                                Retour à la liste
                            </Link>
                        </div>
                    )}
                </div>
            </div>

            <AlertDialog open={confirming} onOpenChange={(open) => !open && !processing && setConfirming(false)}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>{active ? 'Désactiver ce compte ?' : 'Activer ce compte ?'}</AlertDialogTitle>
                        <AlertDialogDescription>
                            {active
                                ? `${user.name} ne pourra plus se connecter à l'espace d'administration.`
                                : `${user.name} pourra de nouveau se connecter à l'espace d'administration.`}
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel asChild>
                            <Button variant="outline" size="sm" className="h-10" disabled={processing}>
                                Annuler
                            </Button>
                        </AlertDialogCancel>
                        <AlertDialogAction asChild>
                            <Button
                                size="sm"
                                className={active ? 'h-10 bg-red-600 hover:bg-red-700' : 'h-10'}
                                disabled={processing}
                                onClick={(event) => {
                                    event.preventDefault();
                                    toggleStatus();
                                }}
                            >
                                {active ? 'Désactiver' : 'Activer'}
                            </Button>
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AdminLayout>
    );
}
