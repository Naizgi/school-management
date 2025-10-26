<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Guardian;
use App\Models\Instructor;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class UserController extends Controller
{
    public function register(Request $request)
    {
        try {
            $request->validate([
                'user_name' => 'required|string|unique:users',
                'phone_number' => 'required|string|unique:users',
                'password' => 'required|string|min:6',
                'role' => 'required|string|in:parent,instructor,admin', // Changed to lowercase
                'first_name' => 'required|string',
                'last_name' => 'required|string',
                'email' => 'sometimes|email|unique:users',
                'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
            ]);

            // Handle file upload
            $profilePicturePath = null;
            if ($request->hasFile('profile_picture')) {
                $profilePicturePath = $this->storeProfilePicture($request->file('profile_picture'));
            }

            // ✅ Create the user
            $userData = [
                'user_name' => $request->user_name,
                'phone_number' => $request->phone_number,
                'password' => Hash::make($request->password),
                'role' => strtolower($request->role), // Ensure lowercase
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'profile_picture' => $profilePicturePath,
                'status' => 'active',
            ];

            $user = User::create($userData);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not saved'
                ], 500);
            }

            // Create role-specific records
            if ($user->role === 'parent') {
                Guardian::create([
                    'user_id' => $user->id
                ]);
            }

            if ($user->role === 'instructor') {
                Instructor::create([
                    'user_id' => $user->id,
                    'name' => $user->full_name
                ]);
            }

            // ✅ Generate JWT Token
            $token = JWTAuth::fromUser($user);

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully',
                'user' => $user->toArrayWithDetails(),
                'token' => $token
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Registration failed',
                'message' => $e->getMessage()
            ], 500);
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
                'users.*.role' => 'required|string|in:parent,instructor,admin',
                'users.*.first_name' => 'required|string',
                'users.*.last_name' => 'required|string',
                'users.*.email' => 'sometimes|email|unique:users',
            ]);

            // Initialize an array to hold registered users' data
            $registeredUsers = [];
            $failedRegistrations = [];

            foreach ($request->users as $index => $userData) {
                try {
                    // Handle base64 profile pictures for bulk upload
                    $profilePicturePath = null;
                    if (isset($userData['profile_picture_base64']) && $this->isBase64Image($userData['profile_picture_base64'])) {
                        $profilePicturePath = $this->storeBase64Image($userData['profile_picture_base64']);
                    }

                    // Create each user
                    $user = User::create([
                        'user_name' => $userData['user_name'],
                        'phone_number' => $userData['phone_number'],
                        'password' => Hash::make($userData['password']),
                        'role' => strtolower($userData['role']),
                        'first_name' => $userData['first_name'],
                        'last_name' => $userData['last_name'],
                        'email' => $userData['email'] ?? null,
                        'profile_picture' => $profilePicturePath,
                        'status' => 'active',
                    ]);

                    // Create role-specific records
                    if ($user->role === 'parent') {
                        Guardian::create([
                            'user_id' => $user->id,
                        ]);
                    }

                    if ($user->role === 'instructor') {
                        Instructor::create([
                            'user_id' => $user->id,
                            'name' => $user->full_name
                        ]);
                    }

                    // Save the user's registration details to the response array
                    $registeredUsers[] = [
                        'user' => $user->toArrayWithDetails(),
                        'token' => JWTAuth::fromUser($user),
                    ];

                } catch (\Exception $e) {
                    $failedRegistrations[] = [
                        'index' => $index,
                        'data' => $userData,
                        'error' => $e->getMessage()
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Bulk registration completed',
                'registered_count' => count($registeredUsers),
                'failed_count' => count($failedRegistrations),
                'registered_users' => $registeredUsers,
                'failed_registrations' => $failedRegistrations,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
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
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials - User not found'
                ], 401);
            }

            // Check if user is active
            if (!$user->isActive()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account is not active. Please contact administrator.'
                ], 401);
            }

            \Log::info('User found:', ['id' => $user->id, 'phone_number' => $user->phone_number]);

            // Verify password manually
            if (!Hash::check($credentials['password'], $user->password)) {
                \Log::error('Password mismatch for user: ' . $user->phone_number);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials - Password incorrect'
                ], 401);
            }

            // Generate token
            $token = JWTAuth::fromUser($user);

            // Record login
            $user->recordLogin();

            \Log::info('User authenticated:', ['id' => $user->id]);

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'user' => $user->toArrayWithDetails(),
                'token' => $token,
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Login error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Server error', 
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully'
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to logout', 
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getProfile(Request $request)
    {
        try {
            // Ensure user is authenticated
            if (!auth()->check()) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not authenticated'
                ], 401);
            }

            $user = auth()->user()->load(['guardian', 'instructor']);

            return response()->json([
                'success' => true,
                'user' => $user->toArrayWithDetails(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong!',
                'error' => config('app.debug') ? $e->getMessage() : 'Please contact support.',
            ], 500);
        }
    }

    public function updateProfile(Request $request)
    {
        try {
            $user = auth()->user();

            $validatedData = $request->validate([
                'first_name' => 'sometimes|string|max:255',
                'last_name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $user->id,
                'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
                'remove_profile_picture' => 'sometimes|boolean',
            ]);

            // Handle profile picture removal
            if ($request->boolean('remove_profile_picture')) {
                $user->deleteProfilePicture();
                unset($validatedData['profile_picture']);
            }

            // Handle new profile picture upload
            if ($request->hasFile('profile_picture')) {
                $uploadSuccess = $user->updateProfilePicture($request->file('profile_picture'));
                if (!$uploadSuccess) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Failed to upload profile picture'
                    ], 500);
                }
                unset($validatedData['profile_picture']);
            }

            $user->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'user' => $user->fresh()->toArrayWithDetails()
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Profile update failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Profile update failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function uploadProfilePicture(Request $request)
    {
        try {
            $user = auth()->user();

            $request->validate([
                'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            ]);

            $uploadSuccess = $user->updateProfilePicture($request->file('profile_picture'));

            if (!$uploadSuccess) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to upload profile picture'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Profile picture updated successfully',
                'profile_picture_url' => $user->profile_picture_url
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Profile picture upload failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Upload failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function changePassword(Request $request)
    {
        try {
            $user = auth()->user();

            $validatedData = $request->validate([
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:6|confirmed',
            ]);

            // Verify current password
            if (!Hash::check($validatedData['current_password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Current password is incorrect'
                ], 400);
            }

            // Update password
            $user->update([
                'password' => Hash::make($validatedData['new_password'])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully'
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Password change failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Password change failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store profile picture and return path
     */
    private function storeProfilePicture($file)
    {
        $filename = 'user_profile_' . Str::random(20) . '_' . time() . '.' . $file->getClientOriginalExtension();
        
        // Resize and optimize image
        $image = Image::make($file);
        $image->resize(400, 400, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        })->encode($file->getClientOriginalExtension(), 85);
        
        $path = 'users/profiles/' . $filename;
        Storage::disk('public')->put($path, $image);
        
        return $path;
    }

    /**
     * Store base64 image and return path
     */
    private function storeBase64Image($base64Image)
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
            $image = substr($base64Image, strpos($base64Image, ',') + 1);
            $type = strtolower($type[1]);

            if (!in_array($type, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                throw new \Exception('Invalid image type');
            }

            $image = str_replace(' ', '+', $image);
            $image = base64_decode($image);

            if ($image === false) {
                throw new \Exception('Base64 decode failed');
            }
        } else {
            throw new \Exception('Invalid base64 image format');
        }

        $filename = 'user_profile_' . Str::random(20) . '_' . time() . '.' . $type;
        $path = 'users/profiles/' . $filename;
        
        Storage::disk('public')->put($path, $image);
        
        return $path;
    }

    /**
     * Check if string is base64 image
     */
    private function isBase64Image($string)
    {
        return strpos($string, 'data:image/') === 0;
    }
}