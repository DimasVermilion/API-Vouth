<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
   public function register(Request $request)
{
    $validator = Validator::make($request->all(), [
        'username' => 'required|string|unique:users|max:255',
        'email'    => 'required|string|email|unique:users',
        'password' => 'required|string|min:8',
        'role'     => 'required|in:admin,siswa',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $user = User::create([
        'username' => $request->username,
        'email'    => $request->email,
        'password' => Hash::make($request->password),
        'role'     => $request->role,
    ]);

    return response()->json(['message' => 'Registrasi Berhasil sebagai ' . $user->role], 201);
}

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Email atau Password salah.'
            ], 401);
        }
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }
   public function changeEmail(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email|unique:users,email,' . auth()->id(),
        'password' => 'required',
    ], [
        'email.required' => 'Email wajib diisi.',
        'email.email'    => 'Format email tidak valid.',
        'email.unique'   => 'Email ini sudah digunakan oleh pengguna lain.',
        'password.required' => 'Password konfirmasi wajib diisi.'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors'  => $validator->errors()
        ], 422);
    }

    $user = auth()->user();

    if (!Hash::check($request->password, $user->password)) {
        return response()->json([
            'success' => false,
            'message' => 'Password konfirmasi salah!'
        ], 401);
    }

    $user->update(['email' => $request->email]);

    return response()->json([
        'success' => true,
        'message' => 'Email berhasil diperbarui!'
    ]);
}

public function changePassword(Request $request)
{
    $validator = Validator::make($request->all(), [
        'current_password' => 'required',
        'new_password' => ['required', 'confirmed', Password::min(8)],
    ], [
        'current_password.required' => 'Password lama wajib diisi.',
        'new_password.required'     => 'Password baru wajib diisi.',
        'new_password.confirmed'    => 'Konfirmasi password baru tidak cocok.',
        'new_password.min'          => 'Password baru minimal harus 8 karakter.',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors'  => $validator->errors()
        ], 422);
    }

    $user = auth()->user();

    if (!Hash::check($request->current_password, $user->password)) {
        return response()->json([
            'success' => false,
            'message' => 'Password lama tidak sesuai!'
        ], 401);
    }

    $user->update([
        'password' => Hash::make($request->new_password)
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Password berhasil diganti!'
    ]);
}
}