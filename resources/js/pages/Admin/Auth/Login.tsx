import { LoginPage, type LoginPageProps } from '@/components/auth/login-page';

export default function Login(props: LoginPageProps) {
    return (
        <LoginPage
            {...props}
            subtitle="Accédez à votre espace administratif."
            emailPlaceholder="admin@crou.ci"
            storageKey="crou_admin_login_email"
        />
    );
}
