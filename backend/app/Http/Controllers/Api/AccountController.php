<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * "Account settings" for the logged-in user.
 */
class AccountController extends Controller
{
    /**
     * PUT /api/user  {name, email}
     */
    public function update(Request $request): UserResource
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        ]);

        $user->update($validated);

        return new UserResource($user);
    }

    /**
     * PUT /api/user/password  {current_password, password, password_confirmation}
     * Logs out every other device, keeping only the token used for this request.
     */
    public function updatePassword(Request $request): Response
    {
        $request->validate([
            'current_password' => ['required', 'current_password:sanctum'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ]);
        $user = $request->user();

        $user->update(['password' => $request->input('password')]);
        $user->tokens()->whereKeyNot($user->currentAccessToken()->id)->delete();

        return response()->noContent();
    }

    /**
     * DELETE /api/user  {password}
     * Deletes the account with its cart, wishlist, reviews and orders. Keys that were
     * sold stay marked as sold, so they can never be sold to someone else.
     */
    public function destroy(Request $request): Response
    {
        $request->validate(['password' => ['required', 'current_password:sanctum']]);

        $user = $request->user();
        $user->tokens()->delete();
        $user->delete();

        return response()->noContent();
    }
}
