<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    /**
     * Starts (or restarts) 2FA setup - generates a fresh secret but does
     * NOT enable enforcement yet. That only happens once confirm() proves
     * the user's authenticator app actually has it right.
     */
    public function enable(Request $request): JsonResponse
    {
        $user = $request->user();
        $google2fa = new Google2FA;

        $secret = $google2fa->generateSecretKey();
        $user->two_factor_secret = $secret;
        $user->two_factor_confirmed_at = null;
        $user->two_factor_recovery_codes = null;
        $user->save();

        return response()->json([
            'secret' => $secret,
            'otpauth_url' => $google2fa->getQRCodeUrl(config('app.name'), $user->email, $secret),
        ]);
    }

    public function confirm(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => 'required|string']);
        $user = $request->user();

        if (! $user->two_factor_secret) {
            abort(400, 'Two-factor setup has not been started.');
        }

        $google2fa = new Google2FA;
        if (! $google2fa->verifyKey($user->two_factor_secret, $data['code'])) {
            abort(422, 'Invalid code.');
        }

        // Shown to the user exactly once, same as a node's daemon token -
        // only the (encrypted) hashes-equivalent stays in the DB via the
        // model's own encrypted:array cast.
        $recoveryCodes = collect(range(1, 8))
            ->map(fn () => Str::random(10).'-'.Str::random(10))
            ->all();

        $user->two_factor_confirmed_at = now();
        $user->two_factor_recovery_codes = $recoveryCodes;
        $user->save();

        return response()->json(['recovery_codes' => $recoveryCodes]);
    }

    public function disable(Request $request): JsonResponse
    {
        $request->validate(['password' => 'required|string']);

        if (! Hash::check($request->input('password'), $request->user()->password)) {
            abort(422, 'Incorrect password.');
        }

        $user = $request->user();
        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->save();

        return response()->json(null, 204);
    }
}
