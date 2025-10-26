<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\User;
use App\Models\Guardian;
use Illuminate\Support\Facades\Log;

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
                         ->where('role', 'Parent')
                         ->first();

            if (!$parent) {
                return response()->json(['error' => 'Parent not found'], 404);
            }

            $students = Student::where('parent_phone_number', $parentPhone)
                ->with('class')
                ->get();

            return response()->json(['students' => $students], 200);
        } catch (\Exception $e) {
            Log::error('Get students error: ' . $e->getMessage());
            return response()->json(
                ['error' => 'Failed to fetch students', 'message' => $e->getMessage()], 
                500
            );
        }
    }

    public function registerStudent(Request $request)
    {
        Log::info('Register student request', $request->all());
    
        try {
            // Remove admin check since we're not using JWT
            // You might want to add some other form of authentication here
            
            $validatedData = $request->validate([
                'parent_phone_number' => 'required|string|exists:users,phone_number',
                'name' => 'required|string|max:255',
                'class_id' => 'required|integer|exists:classes,id',
                'roll_number' => 'required|string|unique:students,roll_number',
                'academic_year' => 'required|string|max:50',
                'date_of_admission' => 'required|date',
                'father_name' => 'required|string|max:255',
                'mother_name' => 'required|string|max:255',
                'date_of_birth' => 'required|date',
                'address' => 'required|string|max:500',
                'profile_picture' => 'nullable|string|url',
            ]);
    
            // Calculate age from date of birth
            $age = now()->diffInYears($validatedData['date_of_birth']);
            
            $student = Student::create([
                'parent_phone_number' => $validatedData['parent_phone_number'],
                'name' => $validatedData['name'],
                'class_id' => $validatedData['class_id'],
                'roll_number' => $validatedData['roll_number'],
                'academic_year' => $validatedData['academic_year'],
                'date_of_admission' => $validatedData['date_of_admission'],
                'father_name' => $validatedData['father_name'],
                'mother_name' => $validatedData['mother_name'],
                'date_of_birth' => $validatedData['date_of_birth'],
                'age' => $age,
                'address' => $validatedData['address'],
                'profile_picture' => $validatedData['profile_picture'] ?? 'default-profile.png',
            ]);
    
            return response()->json([
                'message' => 'Student registered successfully',
                'student' => $student
            ], 201);
        } catch (\Exception $e) {
            Log::error('Student registration failed: ' . $e->getMessage());
            return response()->json(
                ['error' => 'Registration failed', 'message' => $e->getMessage()], 
                500
            );
        }
    }

    public function updateStudentProfile(Request $request, $student_id)
    {
        try {
            $parentPhone = $request->input('parent_phone_number');
            
            if (!$parentPhone) {
                return response()->json(['error' => 'Parent phone number is required'], 400);
            }

            $student = Student::where('id', $student_id)
                ->where('parent_phone_number', $parentPhone)
                ->first();

            if (!$student) {
                return response()->json(['error' => 'Student not found or unauthorized'], 404);
            }

            $validatedData = $request->validate([
                'name' => 'sometimes|string',
                'class_id' => 'sometimes|integer|exists:classes,id',
                'profile_picture' => 'nullable|string|url',
            ]);

            $student->update($validatedData);

            return response()->json([
                'message' => 'Profile updated successfully',
                'student' => $student
            ], 200);
        } catch (\Exception $e) {
            Log::error('Profile update failed: ' . $e->getMessage());
            return response()->json(
                ['error' => 'Update failed', 'message' => $e->getMessage()], 
                500
            );
        }
    }
}