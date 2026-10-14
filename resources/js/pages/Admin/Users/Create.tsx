import { AdminLayout } from '@/components/layout/admin-layout';
import { UserForm, type Option, type PasswordPolicy } from '@/components/users/user-form';

type UsersCreateProps = {
    genres: Option[];
    roles: Option[];
    canAssignRole: boolean;
    telephonePrefixes: string[];
    passwordPolicy: PasswordPolicy;
    storeUrl: string;
    indexUrl: string;
};

export default function UsersCreate({ storeUrl, indexUrl, ...props }: UsersCreateProps) {
    return (
        <AdminLayout title="Nouvel utilisateur">
            <div className="mb-6 flex flex-col gap-1">
                <h1 className="page-title text-xl font-medium text-gray-900">Nouvel utilisateur</h1>
                <p className="text-sm text-muted">Créer un compte d'accès à l'espace d'administration.</p>
            </div>

            <div className="mx-auto w-full max-w-4xl">
                <UserForm mode="create" submitUrl={storeUrl} cancelUrl={indexUrl} {...props} />
            </div>
        </AdminLayout>
    );
}
