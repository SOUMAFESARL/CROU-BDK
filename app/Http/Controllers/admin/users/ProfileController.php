<?php
namespace App\Http\Controllers\admin\users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user('admin');
        abort_unless($user, 401);
        $user->loadMissing(['genre', 'role']);
        return view('admin.users.profile', compact('user'));
    }

    public function updatePassword(Request $request): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user('admin');
        abort_unless($user, 401);
        $data = $request->validate([
            'password' => ['required', 'string', 'confirmed', \App\Support\SystemSecurity::passwordRule()],
        ], [
            'password.required' => 'Renseignez le nouveau mot de passe.',
            'password.confirmed' => 'La confirmation ne correspond pas au nouveau mot de passe.',
        ]);
        $user->password = $data['password'];
        $user->setRememberToken(\Illuminate\Support\Str::random(60));
        $user->save();
        $request->session()->regenerate();
        return redirect()->route('admin.profile.show')->with('success', 'Votre mot de passe a été modifié.');
    }
}