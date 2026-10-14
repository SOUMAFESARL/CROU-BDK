import { LoginPage, type LoginPageProps } from '@/components/auth/login-page';

type EtudiantLoginProps = LoginPageProps & {
    registerUrl: string;
};

export default function Login({ registerUrl, ...props }: EtudiantLoginProps) {
    return (
        <LoginPage
            {...props}
            subtitle="Accédez à votre espace étudiant."
            emailPlaceholder="etudiant@exemple.com"
            storageKey="crou_etudiant_login_email"
            buttonVariant="accent"
            footer={
                <>
                    Pas encore de compte ?{' '}
                    <a href={registerUrl} className="font-semibold text-primary hover:underline">
                        Créer un compte
                    </a>
                </>
            }
        />
    );
}
