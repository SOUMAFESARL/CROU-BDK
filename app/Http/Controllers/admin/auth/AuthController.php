<?php

namespace App\Http\Controllers\admin\auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use App\Support\SystemSecurity;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Auth/Login', [
            'loginUrl' => route('admin.post.login'),
            'forgotUrl' => route('admin.forgot'),
            'csrfToken' => csrf_token(),
            'oldEmail' => old('email'),
            'logoUrl' => systemLogo('admin/assets/images/logos/crou_bdk.jpeg'),
            'backgroundUrl' => asset('files/assets/images/auth/crou_bondoukou.jpg'),
            'year' => now()->year,
        ]);
    }
     public function forgot()
    {
        return view('admin.auth.forgot');
    }

    public function login(Request $request) : RedirectResponse
    {
        //dd($request->all());
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);
       // dd(Auth::guard('admin')->attempt($credentials + ['statut' => 'active']));
        $loginKey = SystemSecurity::loginKey($request, 'admin');
        if (SystemSecurity::tooManyAttempts($loginKey)) {
            return back()->withErrors(['email' => 'Compte temporairement bloqué. Réessayez dans '.SystemSecurity::remainingSeconds($loginKey).' secondes.'])->onlyInput('email');
        }
        if (Auth::guard('admin')->attemptWhen($credentials, fn (User $user) => in_array($user->statut, ['active', 'en_attente', null], true))) {
            SystemSecurity::clear($loginKey);
            $authenticatedUser = Auth::guard('admin')->user();
            if (systemConfig('two_factor_auth', false)) {
                if (systemConfig('two_factor_method', 'email') !== 'email') {
                    Auth::guard('admin')->logout();
                    return back()->withErrors(['email' => 'La méthode 2FA sélectionnée nécessite une intégration qui n’est pas encore configurée. Utilisez la méthode E-mail.']);
                }
                $code = (string) random_int(100000, 999999);
                $request->session()->put('admin_2fa', [
                    'user_id' => (string) $authenticatedUser->getAuthIdentifier(),
                    'code' => Hash::make($code),
                    'expires_at' => now()->addMinutes(10)->timestamp,
                ]);
                Auth::guard('admin')->logout();
                try {
                    Mail::raw("Votre code de vérification est : {$code}. Il expire dans 10 minutes.", fn ($message) => $message->to($authenticatedUser->email)->subject('Code de vérification'));
                } catch (\Throwable $exception) {
                    $request->session()->forget('admin_2fa');
                    report($exception);
                    return back()->withErrors(['email' => 'Le code 2FA n’a pas pu être envoyé. Vérifiez la configuration e-mail.']);
                }
                return redirect()->route('admin.two-factor.form');
            }
          //  dd('fdbnfdlnjnfd');
            $request->session()->regenerate();
            SystemSecurity::invalidateOtherSessions($request, (string) Auth::guard('admin')->id());
            // dd('hjndhjgf');
            //  dd(Auth::guard('admin')->user(),Auth::guard('admin')->check());
            return redirect()->intended(route('admin.dashboard.index')); // Redirection vers l'admin
        }
        SystemSecurity::hit($loginKey);
        return  back()->withErrors(['error' =>"Veillez vérifier votre email ou votre mot de passe."]);
    }

    public function twoFactorForm()
    {
        abort_unless(session()->has('admin_2fa'), 403);
        return view('admin.auth.two-factor');
    }

    public function twoFactorVerify(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'digits:6']]);
        $pending = $request->session()->get('admin_2fa');
        if (!$pending || now()->timestamp > ($pending['expires_at'] ?? 0) || !Hash::check($request->code, $pending['code'] ?? '')) {
            return back()->withErrors(['code' => 'Code invalide ou expiré.']);
        }
        $user = User::findOrFail($pending['user_id']);
        if (!in_array($user->statut, ['active', 'en_attente', null], true)) {
            $request->session()->forget('admin_2fa');
            return redirect()->route('admin.login')->withErrors(['email' => 'Votre compte est désactivé.']);
        }
        Auth::guard('admin')->login($user);
        $request->session()->forget('admin_2fa');
        $request->session()->regenerate();
        SystemSecurity::invalidateOtherSessions($request, (string) $user->getAuthIdentifier());
        return redirect()->intended(route('admin.dashboard.index'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
