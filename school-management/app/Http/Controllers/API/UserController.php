<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function register(Request $request)
    {
        try {
            $request->validate([
                'user_name' => 'required|string|unique:users',
                'phone_number' => 'required|string|unique:users',
                'password' => 'required|string|min:6',
                'role' => 'required|string|in:Parent,Instructor,Admin',
                'first_name' => 'required|string',
                'last_name' => 'required|string',
            ]);
    
            $user = User::create([
                'user_name' => $request->user_name,
                'phone_number' => $request->phone_number,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
            ]);
    
            if (!$user) {
                return response()->json(['message' => 'User not saved'], 500);
            }
    
            return response()->json([
                'message' => 'User registered successfully',
                'user' => $user, // Add this to confirm user data
                'token' => $user->createToken('auth_token')->plainTextToken
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    

   
public function login(Request $request)
{
    $request->validate([
        'phone_number' => 'required|string',
        'password' => 'required|string',
    ]);

    $user = User::where('phone_number', $request->phone_number)->first();

    if (!$user) {
        return response()->json(['message' => 'User not found'], 404);
    }

    if (!Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    try {
        // ✅ Ensure user ID exists before generating token
        if (!$user->id) {
            throw new \Exception("User ID is missing, cannot generate token");
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => [
                'id' => $user->id,
                'user_name' => $user->user_name,
                'phone_number' => $user->phone_number,
                'role' => $user->role,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
            ],
            'token' => $token,
        ], 200);
    } catch (\Exception $e) {
        Log::error('Login Error: ' . $e->getMessage()); 
        return response()->json(['message' => 'Something went wrong', 'error' => $e->getMessage()], 500);
    }
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
