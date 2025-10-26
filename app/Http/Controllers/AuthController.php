<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    // User login - FIXED VERSION
    public function login(Request $request)
    {
        \Log::info('Attempting login with:', $request->all());
    
        $credentials = $request->validate([
            'phone_number' => 'required|string',
            'password' => 'required|string',
        ]);
    
        // Use JWTAuth directly or specify the guard
        try {
            // Method 1: Using JWTAuth directly
            if (!$token = JWTAuth::attempt($credentials)) {
                \Log::error('Invalid credentials');
                return response()->json(['error' => 'Invalid credentials'], 401);
            }
            
            // Method 2: Using auth with guard specified
            // if (!$token = auth('api')->attempt($credentials)) {
            //     \Log::error('Invalid credentials');
            //     return response()->json(['error' => 'Invalid credentials'], 401);
            // }
    
            $user = auth('api')->user();
            
            return response()->json([
                'message' => 'Login successful',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone_number' => $user->phone_number,
                ],
                'token' => $token
            ]);
            
        } catch (JWTException $e) {
            \Log::error('JWT Exception: ' . $e->getMessage());
            return response()->json(['error' => 'Could not create token'], 500);
        }
    }

    // User registration - FIXED VERSION
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|unique:users',
            'password' => 'required|string|min:6',
        ]);

        // Normalize phone number
        $phone_number = preg_replace('/[^0-9]/', '', $request->phone_number);

        $user = User::create([
            'name' => $request->name,
            'phone_number' => $phone_number,
            'password' => bcrypt($request->password),
        ]);

        // Generate token for the new user
        $token = JWTAuth::fromUser($user);

        return $this->respondWithToken($token, $user);
    }

    // Get logged-in user - FIXED VERSION
    public function me()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }
            return response()->json([
                'id' => $user->id,
                'name' => $user->name,
                'phone_number' => $user->phone_number,
            ]);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Invalid token'], 401);
        }
    }

    // Logout user - FIXED VERSION
    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json(['message' => 'Successfully logged out']);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Failed to logout'], 500);
        }
    }

    // Refresh token - FIXED VERSION
    public function refresh()
    {
        try {
            $token = JWTAuth::refresh(JWTAuth::getToken());
            $user = JWTAuth::setToken($token)->authenticate();
            return $this->respondWithToken($token, $user);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Token refresh failed'], 401);
        }
    }

    // Helper function to return token response
    protected function respondWithToken($token, $user)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60, // Fixed: was 600 which is 10 hours
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone_number' => $user->phone_number,
            ],
        ]);
    }
}