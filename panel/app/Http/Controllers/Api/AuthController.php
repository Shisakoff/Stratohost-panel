<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FA\Google2FA;

class AuthController extends Controller
{
    // Session key holding the id of a user who passed the password check
    // but still owes a 2FA code - deliberately NOT logged in yet, so
    // hitting any auth:sanctum route in between does nothing.
    private const PENDING_2FA_SESSION_KEY = 'auth.pending_two_factor_user_id';

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (! Auth::validate($credentials)) {
            abort(422, 'Those credentials do not match our records.');
        }

        /** @var User $user */
        $user = User::where('email', $credentials['email'])->firstOrFail();

        if ($user->hasTwoFactorEnabled()) {
            $request->session()->put(self::PENDING_2FA_SESSION_KEY, $user->id);

            return response()->json(['two_factor' => true]);
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return response()->json($user);
    }

    public function twoFactorChallenge(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => 'required|string']);

        $userId = $request->session()->get(self::PENDING_2FA_SESSION_KEY);
        if (! $userId) {
            abort(422, 'No pending login to verify.');
        }

        /** @var User $user */
        $user = User::findOrFail($userId);

        if (! $this->verifyTwoFactorCode($user, $data['code'])) {
            abort(422, 'Invalid code.');
        }

        $request->session()->forget(self::PENDING_2FA_SESSION_KEY);
        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return response()->json($user);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(null, 204);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    private function verifyTwoFactorCode(User $user, string $code): bool
    {
        $google2fa = new Google2FA;
        if ($google2fa->verifyKey($user->two_factor_secret, $code)) {
            return true;
        }

        $recoveryCodes = $user->two_factor_recovery_codes ?? [];
        if (in_array($code, $recoveryCodes, true)) {
            // Recovery codes are single-use.
            $user->two_factor_recovery_codes = array_values(array_diff($recoveryCodes, [$code]));
            $user->save();

            return true;
        }

        return false;
    }
}
