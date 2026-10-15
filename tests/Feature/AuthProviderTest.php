<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AuthProviderTest extends TestCase
{
    public function test_admin_and_web_guards_resolve_the_user_provider(): void
    {
        foreach (['admin', 'web'] as $guard) {
            $provider = Auth::guard($guard)->getProvider();
            $this->assertInstanceOf(EloquentUserProvider::class, $provider);
            $this->assertSame(User::class, $provider->getModel());
        }

        $this->assertInstanceOf(EloquentUserProvider::class, Auth::createUserProvider(config('auth.passwords.users.provider')));
    }
}
