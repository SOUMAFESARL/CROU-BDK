<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Models\Etudiant;
use App\Notifications\EtudiantResetPasswordNotification;
use App\Support\SystemSecurity;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Mot de passe oublié de l'espace étudiant : envoi d'un lien par e-mail puis choix d'un nouveau mot de passe.
 * Le broker « etudiants » (config/auth.php) stocke les jetons dans etudiant_password_reset_tokens.
 */
class EtudiantPasswordResetController extends Controller
{
    /** Message identique que le compte existe ou non, pour ne pas révéler les adresses inscrites. */
    private const LINK_SENT = 'Si un compte étudiant correspond à cette adresse, un lien de réinitialisation vient de lui être envoyé. Pensez à vérifier vos courriers indésirables.';

    public function request(): Response
    {
        return Inertia::render('Etudiant/Auth/ForgotPassword', [
            ...$this->layoutProps(),
            'submitUrl' => route('etudiant.password.email'),
            'loginUrl' => route('etudiant.login'),
            'csrfToken' => csrf_token(),
            'oldEmail' => old('email'),
            'status' => session('status'),
        ]);
    }

    public function email(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email']], [
            'email.required' => "L'adresse e-mail est obligatoire.",
            'email.email' => 'Adresse e-mail invalide.',
        ]);

        try {
            Password::broker('etudiants')->sendResetLink(['email1' => $data['email']], function (Etudiant $etudiant, string $token) {
                $url = route('etudiant.password.reset', ['token' => $token, 'email' => $etudiant->email1]);
                $expire = (int) config('auth.passwords.etudiants.expire', 60);
                Notification::route('mail', $etudiant->email1)->notify(new EtudiantResetPasswordNotification($url, $expire));
            });
        } catch (\Throwable $exception) {
            report($exception);
            throw ValidationException::withMessages([
                'email' => "L'e-mail n'a pas pu être envoyé pour le moment. Réessayez plus tard ou contactez le CROU.",
            ]);
        }

        return back()->with('status', self::LINK_SENT);
    }

    public function edit(Request $request, string $token): Response
    {
        return Inertia::render('Etudiant/Auth/ResetPassword', [
            ...$this->layoutProps(),
            'submitUrl' => route('etudiant.password.update'),
            'loginUrl' => route('etudiant.login'),
            'csrfToken' => csrf_token(),
            'token' => $token,
            'email' => old('email', (string) $request->query('email', '')),
            'passwordHint' => $this->passwordHint(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'confirmed', SystemSecurity::passwordRule()],
        ], [
            'email.required' => "L'adresse e-mail est obligatoire.",
            'email.email' => 'Adresse e-mail invalide.',
            'password.required' => 'Renseignez le nouveau mot de passe.',
            'password.confirmed' => 'La confirmation ne correspond pas au nouveau mot de passe.',
        ]);

        $status = Password::broker('etudiants')->reset(
            ['email1' => $data['email'], 'token' => $data['token'], 'password' => $data['password']],
            function (Etudiant $etudiant, string $password) {
                // Pas de setRememberToken() : la table etudiants n'a pas de colonne remember_token.
                $etudiant->password = Hash::make($password);
                $etudiant->save();
                event(new PasswordReset($etudiant));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => 'Ce lien de réinitialisation est invalide ou a expiré. Faites une nouvelle demande.',
            ]);
        }

        return redirect()->route('etudiant.login')
            ->with('success', 'Votre mot de passe a été réinitialisé. Vous pouvez vous connecter.');
    }

    private function layoutProps(): array
    {
        return [
            'logoUrl' => asset('admin/assets/images/logos/crou_bdk.jpeg'),
            'backgroundUrl' => asset('files/assets/images/auth/crou_bondoukou.jpg'),
            'year' => now()->year,
        ];
    }

    private function passwordHint(): string
    {
        $rules = [(int) systemConfig('password_min_length', 8).' caractères minimum'];
        if (systemConfig('password_require_uppercase', true)) $rules[] = 'majuscules et minuscules';
        if (systemConfig('password_require_number', true)) $rules[] = 'au moins un chiffre';
        if (systemConfig('password_require_special', false)) $rules[] = 'au moins un caractère spécial';

        return ucfirst(implode(', ', $rules)).'.';
    }
}
