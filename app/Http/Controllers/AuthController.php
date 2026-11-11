<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private function session(User $user, int $status = 200): JsonResponse
    {
        return response()->json([
            'token' => $user->createToken('rentkh')->plainTextToken,
            'user' => new UserResource($user),
        ], $status);
    }

    private function findByIdentifier(string $identifier): ?User
    {
        $identifier = trim($identifier);
        if (str_contains($identifier, '@')) {
            return User::where('email', mb_strtolower($identifier))->first();
        }
        $digits = User::digits($identifier);

        return $digits === '' ? null : User::where('phone_digits', $digits)->first();
    }

    public function signup(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:100'],
        ]);

        $digits = User::digits($data['phone']);
        if (strlen($digits) < 8) {
            throw ValidationException::withMessages(['phone' => 'Invalid phone number.']);
        }
        if (User::where('phone_digits', $digits)->exists()) {
            throw ValidationException::withMessages(['phone' => 'This phone number is already registered.']);
        }

        $user = User::create([
            'name' => trim($data['name']),
            'phone' => trim($data['phone']),
            'phone_digits' => $digits,
            'email' => isset($data['email']) ? mb_strtolower($data['email']) : null,
            'password' => $data['password'],
            // phone_verified_at stays null until SMS verification is wired up.
        ]);

        return $this->session($user, 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'identifier' => ['required', 'string', 'max:190'],
            'password' => ['required', 'string'],
        ]);

        $user = $this->findByIdentifier($data['identifier']);
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['identifier' => 'Incorrect phone/email or password.']);
        }

        return $this->session($user);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['ok' => true]);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function updateMe(Request $request): UserResource
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'phone' => ['sometimes', 'string', 'max:32'],
            'email' => ['sometimes', 'nullable', 'email', 'max:190', 'unique:users,email,'.$user->id],
            'prefs' => ['sometimes', 'array'],
            'prefs.messages' => ['boolean'],
            'prefs.tips' => ['boolean'],
        ]);

        if (isset($data['phone'])) {
            $digits = User::digits($data['phone']);
            if (strlen($digits) < 8 || User::where('phone_digits', $digits)->where('id', '!=', $user->id)->exists()) {
                throw ValidationException::withMessages(['phone' => 'Invalid or already used phone number.']);
            }
            if ($digits !== $user->phone_digits) {
                $user->phone_verified_at = null; // a new number must be verified again
            }
            $data['phone_digits'] = $digits;
        }
        if (array_key_exists('email', $data) && $data['email']) {
            $data['email'] = mb_strtolower($data['email']);
        }

        $user->fill($data)->save();

        return new UserResource($user);
    }

    /* ---- Password reset: request code -> verify code -> set new password ---- */

    public function forgotRequest(Request $request): JsonResponse
    {
        $data = $request->validate(['identifier' => ['required', 'string', 'max:190']]);
        $user = $this->findByIdentifier($data['identifier']);

        // Always answer the same so nobody can probe which accounts exist.
        if ($user) {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            DB::table('password_reset_codes')->where('user_id', $user->id)->delete();
            DB::table('password_reset_codes')->insert([
                'user_id' => $user->id,
                'code_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes(10),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // TODO: send $code by SMS / email. For now it is written to storage/logs/laravel.log.
            Log::info("Password reset code for user {$user->id}: {$code}");
        }

        return response()->json(['ok' => true]);
    }

    public function forgotVerify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'identifier' => ['required', 'string'],
            'code' => ['required', 'digits:6'],
        ]);

        $user = $this->findByIdentifier($data['identifier']);
        $row = $user ? DB::table('password_reset_codes')->where('user_id', $user->id)->first() : null;

        if (! $row || now()->gt($row->expires_at) || $row->attempts >= 5) {
            throw ValidationException::withMessages(['code' => 'The code is invalid or has expired.']);
        }
        if (! Hash::check($data['code'], $row->code_hash)) {
            DB::table('password_reset_codes')->where('id', $row->id)->increment('attempts');
            throw ValidationException::withMessages(['code' => 'The code is invalid or has expired.']);
        }

        $resetToken = Str::random(48);
        DB::table('password_reset_codes')->where('id', $row->id)->update([
            'verified_at' => now(),
            'reset_token_hash' => hash('sha256', $resetToken),
            'updated_at' => now(),
        ]);

        return response()->json(['resetToken' => $resetToken]);
    }

    public function forgotReset(Request $request): JsonResponse
    {
        $data = $request->validate([
            'resetToken' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'max:100'],
        ]);

        $row = DB::table('password_reset_codes')
            ->where('reset_token_hash', hash('sha256', $data['resetToken']))
            ->whereNotNull('verified_at')
            ->where('expires_at', '>', now())
            ->first();
        if (! $row) {
            throw ValidationException::withMessages(['password' => 'This reset link has expired. Please start again.']);
        }

        $user = User::findOrFail($row->user_id);
        $user->forceFill(['password' => $data['password']])->save();
        $user->tokens()->delete(); // sign out everywhere
        DB::table('password_reset_codes')->where('id', $row->id)->delete();

        return $this->session($user);
    }
}
