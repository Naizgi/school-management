<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'user_name' => 'required|string',
            'phone_number' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('user_name', $request->user_name)
                    ->where('phone_number', $request->phone_number)
                    ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        return response()->json(['token' => $user->createToken('auth_token')->plainTextToken]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate(['phone_number' => 'required|string']);
        return response()->json(['message' => 'Password reset link sent.']);
    }

    public function getProfile(Request $request)
    {
        return response()->json(Auth::user());
    }
}
