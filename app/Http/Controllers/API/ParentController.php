<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\User;
use App\Models\ClassModel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class ParentController extends Controller
{
    public function getStudents(Request $request)
    {
        try {
            // Get parent phone number from request instead of JWT
            $parentPhone = $request->input('parent_phone_number');
            
            if (!$parentPhone) {
                return response()->json(['error' => 'Parent phone number is required'], 400);
            }

            // Verify parent exists
            $parent = User::where('phone_number', $parentPhone)
                         ->where('role', 'parent')
                         ->first();

            if (!$parent) {
                return response()->json(['error' => 'Parent not found'], 404);
            }

            $students = Student::where('parent_phone_number', $parentPhone)
                ->with(['class', 'parent'])
                ->get()
                ->map->toArrayWithDetails();

            return response()->json([
                'success' => true,
                'students' => $students
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get students error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch students', 
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function registerStudent(Request $request)
    {
        Log::info('Register student request', $request->all());
    
        try {
            $validatedData = $request->validate([
                'parent_phone_number' => 'required|string|exists:users,phone_number',
                'name' => 'required|string|max:255',
                'class_id' => 'required|integer|exists:classes,id',
                'roll_number' => 'sometimes|string|unique:students,roll_number', // Made optional for auto-generation
                'academic_year' => 'required|string|max:50',
                'date_of_admission' => 'required|date',
                'father_name' => 'required|string|max:255',
                'mother_name' => 'required|string|max:255',
                'date_of_birth' => 'required|date',
                'address' => 'required|string|max:500',
                'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max, now accepts file
            ]);
    
            // Handle file upload
            $profilePicturePath = null;
            if ($request->hasFile('profile_picture')) {
                $profilePicturePath = $this->storeProfilePicture($request->file('profile_picture'));
            }

            // Calculate age from date of birth
            $age = now()->diffInYears($validatedData['date_of_birth']);
            
            $studentData = [
                'parent_phone_number' => $validatedData['parent_phone_number'],
                'name' => $validatedData['name'],
                'class_id' => $validatedData['class_id'],
                'roll_number' => $validatedData['roll_number'] ?? null, // Let model handle auto-generation
                'academic_year' => $validatedData['academic_year'],
                'date_of_admission' => $validatedData['date_of_admission'],
                'father_name' => $validatedData['father_name'],
                'mother_name' => $validatedData['mother_name'],
                'date_of_birth' => $validatedData['date_of_birth'],
                'address' => $validatedData['address'],
                'profile_picture' => $profilePicturePath,
            ];

            $student = Student::create($studentData);
    
            return response()->json([
                'success' => true,
                'message' => 'Student registered successfully',
                'student' => $student->toArrayWithDetails()
            ], 201);
        } catch (\Exception $e) {
            Log::error('Student registration failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Registration failed', 
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function bulkRegisterStudents(Request $request)
    {
        try {
            $studentsData = $request->all();

            if (!is_array($studentsData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid request format. Expected an array of students.'
                ], 400);
            }

            $registered = [];
            $failed = [];

            foreach ($studentsData as $index => $studentData) {
                try {
                    // Handle both file uploads and base64 images in bulk
                    $profilePicturePath = null;
                    
                    if (isset($studentData['profile_picture_file'])) {
                        // This would require a more complex handling for bulk file uploads
                        // You might want to receive files as separate multipart form data
                        Log::warning('Bulk file upload not fully implemented for index: ' . $index);
                    } elseif (isset($studentData['profile_picture_base64']) && $this->isBase64Image($studentData['profile_picture_base64'])) {
                        $profilePicturePath = $this->storeBase64Image($studentData['profile_picture_base64']);
                    }

                    $validatedData = validator($studentData, [
                        'parent_phone_number' => 'required|string|exists:users,phone_number',
                        'name' => 'required|string|max:255',
                        'class_id' => 'required|integer|exists:classes,id',
                        'roll_number' => 'sometimes|string|unique:students,roll_number',
                        'academic_year' => 'required|string|max:50',
                        'date_of_admission' => 'required|date',
                        'father_name' => 'required|string|max:255',
                        'mother_name' => 'required|string|max:255',
                        'date_of_birth' => 'required|date',
                        'address' => 'required|string|max:500',
                    ])->validate();

                    $student = Student::create([
                        'parent_phone_number' => $validatedData['parent_phone_number'],
                        'name' => $validatedData['name'],
                        'class_id' => $validatedData['class_id'],
                        'roll_number' => $validatedData['roll_number'] ?? null,
                        'academic_year' => $validatedData['academic_year'],
                        'date_of_admission' => $validatedData['date_of_admission'],
                        'father_name' => $validatedData['father_name'],
                        'mother_name' => $validatedData['mother_name'],
                        'date_of_birth' => $validatedData['date_of_birth'],
                        'address' => $validatedData['address'],
                        'profile_picture' => $profilePicturePath,
                    ]);

                    $registered[] = $student->toArrayWithDetails();

                } catch (\Exception $e) {
                    $failed[] = [
                        'index' => $index,
                        'data' => $studentData,
                        'error' => $e->getMessage()
                    ];
                    Log::error("Bulk registration failed for index {$index}: " . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Bulk registration completed',
                'registered_count' => count($registered),
                'failed_count' => count($failed),
                'registered_students' => $registered,
                'failed_students' => $failed
            ], 200);

        } catch (\Exception $e) {
            Log::error('Bulk registration failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Bulk registration failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function updateStudentProfile(Request $request, $student_id)
    {
        try {
            $parentPhone = $request->input('parent_phone_number');
            
            if (!$parentPhone) {
                return response()->json([
                    'success' => false,
                    'error' => 'Parent phone number is required'
                ], 400);
            }

            $student = Student::where('id', $student_id)
                ->where('parent_phone_number', $parentPhone)
                ->first();

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'error' => 'Student not found or unauthorized'
                ], 404);
            }

            $validatedData = $request->validate([
                'name' => 'sometimes|string|max:255',
                'class_id' => 'sometimes|integer|exists:classes,id',
                'roll_number' => 'sometimes|string|unique:students,roll_number,' . $student_id,
                'father_name' => 'sometimes|string|max:255',
                'mother_name' => 'sometimes|string|max:255',
                'address' => 'sometimes|string|max:500',
                'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
                'remove_profile_picture' => 'sometimes|boolean' // Flag to remove existing picture
            ]);

            // Handle profile picture removal
            if ($request->boolean('remove_profile_picture')) {
                $student->deleteProfilePicture();
                unset($validatedData['profile_picture']);
            }

            // Handle new profile picture upload
            if ($request->hasFile('profile_picture')) {
                $uploadSuccess = $student->updateProfilePicture($request->file('profile_picture'));
                if (!$uploadSuccess) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Failed to upload profile picture'
                    ], 500);
                }
                unset($validatedData['profile_picture']); // Remove from direct update since we handled it
            }

            $student->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'student' => $student->fresh()->toArrayWithDetails()
            ], 200);

        } catch (\Exception $e) {
            Log::error('Profile update failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Update failed', 
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function uploadProfilePicture(Request $request, $student_id)
    {
        try {
            $parentPhone = $request->input('parent_phone_number');
            
            if (!$parentPhone) {
                return response()->json([
                    'success' => false,
                    'error' => 'Parent phone number is required'
                ], 400);
            }

            $student = Student::where('id', $student_id)
                ->where('parent_phone_number', $parentPhone)
                ->first();

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'error' => 'Student not found or unauthorized'
                ], 404);
            }

            $request->validate([
                'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            ]);

            $uploadSuccess = $student->updateProfilePicture($request->file('profile_picture'));

            if (!$uploadSuccess) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to upload profile picture'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Profile picture updated successfully',
                'profile_picture_url' => $student->profile_picture_url
            ], 200);

        } catch (\Exception $e) {
            Log::error('Profile picture upload failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Upload failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store profile picture and return path
     */
    private function storeProfilePicture($file)
    {
        $filename = 'student_profile_' . Str::random(20) . '_' . time() . '.' . $file->getClientOriginalExtension();
        
        // Resize and optimize image
        $image = Image::make($file);
        $image->resize(400, 400, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        })->encode($file->getClientOriginalExtension(), 85);
        
        $path = 'students/profiles/' . $filename;
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

        $filename = 'student_profile_' . Str::random(20) . '_' . time() . '.' . $type;
        $path = 'students/profiles/' . $filename;
        
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