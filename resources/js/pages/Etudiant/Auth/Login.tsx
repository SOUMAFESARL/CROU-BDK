import { LoginPage, type LoginPageProps } from '@/components/auth/login-page';

export default function Login(props: LoginPageProps) {
    return (
        <LoginPage
            {...props}
            subtitle="Accédez à votre espace étudiant."
            emailPlaceholder="etudiant@exemple.com"
            storageKey="crou_etudiant_login_email"
            buttonVariant="accent"
            withCaptcha={false}
        />
    );
}
