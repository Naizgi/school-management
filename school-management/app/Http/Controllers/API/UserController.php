<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Guardian;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

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

            // ✅ Create the user
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

         
            if ($user->role === 'Parent') {
                Guardian::create([
                    'user_id' => $user->id
                ]);
            }

            if ($user->role === 'Instructor') {
                Instructor::create([
                    'user_id' => $user->id,
                    'name' => $user->user_name
                ]);
            }

            // ✅ Generate JWT Token
            $token = JWTAuth::fromUser($user);

            return response()->json([
                'message' => 'User registered successfully',
                'user' => $user,
                'token' => $token
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function bulkRegister(Request $request)
    {
        try {
            // Validate the incoming request - an array of users
            $request->validate([
                'users' => 'required|array',
                'users.*.user_name' => 'required|string|unique:users',
                'users.*.phone_number' => 'required|string|unique:users',
                'users.*.password' => 'required|string|min:6',
                'users.*.role' => 'required|string|in:Parent,Instructor,Admin',
                'users.*.first_name' => 'required|string',
                'users.*.last_name' => 'required|string',
            ]);
    
            // Initialize an array to hold registered users' data
            $registeredUsers = [];
    
            foreach ($request->users as $userData) {
                // Create each user
                $user = User::create([
                    'user_name' => $userData['user_name'],
                    'phone_number' => $userData['phone_number'],
                    'password' => Hash::make($userData['password']),
                    'role' => $userData['role'],
                    'first_name' => $userData['first_name'],
                    'last_name' => $userData['last_name'],
                ]);
    
                // If the user is a Parent, create a Guardian record
                if ($user->role === 'Parent') {
                    Guardian::create([
                        'user_id' => $user->id,
                    ]);
                }
    
                // Save the user's registration details to the response array
                $registeredUsers[] = [
                    'user' => $user,
                    'token' => JWTAuth::fromUser($user),  // Generate JWT token for the new user
                ];
            }
    
            return response()->json([
                'message' => 'Users registered successfully',
                'users' => $registeredUsers,
            ], 201);
    
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error registering users',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    




    // ✅ LOGIN
    public function login(Request $request)
    {
        try {
            $credentials = $request->validate([
                'phone_number' => 'required|string',
                'password' => 'required|string',
            ]);
    
            \Log::info('Attempting login with:', $credentials);
    
            // Find the user manually
            $user = User::where('phone_number', $credentials['phone_number'])->first();
    
            if (!$user) {
                \Log::error('User not found with phone number: ' . $credentials['phone_number']);
                return response()->json(['message' => 'Invalid credentials - User not found'], 401);
            }
    
            \Log::info('User found:', ['id' => $user->id, 'phone_number' => $user->phone_number]);
    
            // Log the hashed password stored in DB (DO NOT do this in production)
            \Log::info('Stored hashed password:', [$user->password]);
    
            // Log the entered password
            \Log::info('Entered password:', [$credentials['password']]);
    
            // Verify password manually
            if (!Hash::check($credentials['password'], $user->password)) {
                \Log::error('Password mismatch for user: ' . $user->phone_number);
                return response()->json(['message' => 'Invalid credentials - Password incorrect'], 401);
            }
    
            // Generate token
            $token = JWTAuth::fromUser($user);
    
            \Log::info('User authenticated:', ['id' => $user->id]);
    
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
            \Log::error('Login error: ' . $e->getMessage());
            return response()->json(['message' => 'Server error', 'error' => $e->getMessage()], 500);
        }
    }
    

    public function logout(Request $request)
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json(['message' => 'Logged out successfully']);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Failed to logout', 'message' => $e->getMessage()], 500);
        }
    }




public function getProfile(Request $request)
{
    try {
        // Ensure user is authenticated
        if (!auth()->check()) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        $user = auth()->user()->load(['guardian', 'instructor']);

        return response()->json([
            'success' => true,
            'user' => [
                'user_id' => $user->user_id,
                'user_name' => $user->user_name,
                'phone_number' => $user->phone_number,
                'role' => $user->role,
                'profile_picture' => $user->profile_picture,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'guardian' => $user->guardian ?? null, // Return null if guardian doesn't exist
                'instructor' => $user->instructor ?? null, // Return null if instructor doesn't exist
            ],
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Something went wrong!',
            'error' => config('app.debug') ? $e->getMessage() : 'Please contact support.',
        ], 500);
    }
}



    
    
    
    
}
