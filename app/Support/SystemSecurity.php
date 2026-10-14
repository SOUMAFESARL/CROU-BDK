<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;

class SystemSecurity
{
    public static function passwordRule(): Password
    {
        $rule = Password::min((int) systemConfig('password_min_length', 8));
        if (systemConfig('password_require_uppercase', true)) $rule->mixedCase();
        if (systemConfig('password_require_number', true)) $rule->numbers();
        if (systemConfig('password_require_special', false)) $rule->symbols();
        return $rule;
    }

    public static function loginKey(Request $request, string $guard): string
    {
        return $guard.'|'.mb_strtolower((string) $request->input('email')).'|'.$request->ip();
    }

    public static function tooManyAttempts(string $key): bool
    {
        return RateLimiter::tooManyAttempts($key, (int) systemConfig('max_login_attempts', 5));
    }

    public static function hit(string $key): void
    {
        RateLimiter::hit($key, (int) systemConfig('lockout_duration', 15) * 60);
    }

    public static function clear(string $key): void
    {
        RateLimiter::clear($key);
    }

    public static function remainingSeconds(string $key): int
    {
        return RateLimiter::availableIn($key);
    }

    public static function invalidateOtherSessions(Request $request, string $userId): void
    {
        if (systemConfig('allow_multiple_sessions', true) || config('session.driver') !== 'database') return;
        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $userId)
            ->where('id', '!=', $request->session()->getId())
            ->delete();
    }
}
