<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    // User login
    public function login(Request $request)
    {
        \Log::info('Attempting login with:', $request->all());
    
        $credentials = $request->validate([
            'phone_number' => 'required|string',
            'password' => 'required|string',
        ]);
    
        if (!$token = auth()->attempt($credentials)) {
            \Log::error('Invalid credentials');
            return response()->json(['error' => 'Invalid credentials'], 401);
        }
    
        return response()->json([
            'message' => 'Login successful',
            'user' => auth()->user(),
            'token' => $token
        ]);
    }
    

    // User registration
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|unique:users', // Ensure unique phone number
            'password' => 'required|string|min:6',
        ]);

        // Normalize phone number
        $phone_number = preg_replace('/[^0-9]/', '', $request->phone_number);

        $user = User::create([
            'name' => $request->name,
            'phone_number' => $phone_number,
            'password' => bcrypt($request->password),
        ]);

        $token = JWTAuth::fromUser($user);

        return $this->respondWithToken($token, $user);
    }

    // Get logged-in user
    public function me()
    {
        $user = Auth::guard('api')->user();
        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }
        return response()->json($user);
    }

    // Logout user
    public function logout()
    {
        Auth::guard('api')->logout();
        return response()->json(['message' => 'Successfully logged out']);
    }

    // Refresh token
    public function refresh()
    {
        return $this->respondWithToken(Auth::guard('api')->refresh(), Auth::guard('api')->user());
    }

    // Helper function to return token response
    protected function respondWithToken($token, $user)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 600,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone_number' => $user->phone_number,
            ],
        ]);
    }
}
