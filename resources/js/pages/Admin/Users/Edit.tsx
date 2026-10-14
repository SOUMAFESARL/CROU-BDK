import { AdminLayout } from '@/components/layout/admin-layout';
import { UserForm, type Option, type PasswordPolicy, type UserFormValues } from '@/components/users/user-form';

type UsersEditProps = {
    user: Omit<UserFormValues, 'password'> & { id: string };
    genres: Option[];
    roles: Option[];
    canAssignRole: boolean;
    telephonePrefixes: string[];
    passwordPolicy: PasswordPolicy;
    updateUrl: string;
    cancelUrl: string;
};

export default function UsersEdit({ user, updateUrl, cancelUrl, ...props }: UsersEditProps) {
    const { id, ...initialValues } = user;

    return (
        <AdminLayout title="Modifier l'utilisateur">
            <div className="mb-6 flex flex-col gap-1">
                <h1 className="page-title text-xl font-medium text-gray-900">Modifier l'utilisateur</h1>
            </div>

            <div className="mx-auto w-full max-w-4xl">
                <UserForm key={id} mode="edit" submitUrl={updateUrl} cancelUrl={cancelUrl} initialValues={initialValues} {...props} />
            </div>
        </AdminLayout>
    );
}
