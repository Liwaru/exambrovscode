<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StudentAuthController extends Controller
{
    public function login(Request $request)
    {
        $validated = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required'],
        ]);

        $student = User::query()
            ->where('username', $validated['username'])
            ->where('role', 'student')
            ->first();

        $passwordMatches = $student
            && $this->passwordMatches($validated['password'], $student->password);

        if (! $passwordMatches) {
            return response()->json([
                'message' => 'Username atau password salah.',
            ], 422);
        }

        $token = Str::random(60);

        $student->update([
            'api_token' => hash('sha256', $token),
        ]);

        return response()->json([
            'token' => $token,
            'student' => [
                'id' => $student->getKey(),
                'username' => $student->username,
                'class_name' => $student->class_name,
            ],
        ]);
    }

    private function passwordMatches(string $plainPassword, string $storedPassword): bool
    {
        if (password_get_info($storedPassword)['algo'] !== null) {
            return Hash::check($plainPassword, $storedPassword);
        }

        return hash_equals($storedPassword, $plainPassword);
    }
}
