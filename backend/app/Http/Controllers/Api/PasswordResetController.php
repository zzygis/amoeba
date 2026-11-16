<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

/**
 * "Forgot password?" using Laravel's built-in password broker: it stores a hashed,
 * single-use token in password_reset_tokens (valid 60 minutes) and emails a link to the
 * React app's /reset-password page (see AppServiceProvider).
 */
class PasswordResetController extends Controller
{
    /**
     * POST /api/forgot-password  {email}
     * Always answers the same way, so it can't be used to find out which emails have accounts.
     */
    public function sendLink(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'string', 'email']]);

        Password::sendResetLink($request->only('email'));

        return response()->json([
            'message' => 'If an account exists for that email, we have sent a link to reset the password.',
        ]);
    }

    /**
     * POST /api/reset-password  {token, email, password, password_confirmation}
     */
    public function reset(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->update(['password' => $password]);
                $user->tokens()->delete(); // log out everywhere: the old password may have leaked
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => 'This reset link is invalid or has expired.']);
        }

        return response()->json(['message' => 'Your password has been reset. You can log in now.']);
    }
}
