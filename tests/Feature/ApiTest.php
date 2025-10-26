<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\Course;
use App\Models\Result;
use App\Models\Class;
use App\Models\Attendance;
use App\Models\Instructor;
use Laravel\Sanctum\Sanctum;
use Tymon\JWTAuth\Facades\JWTAuth;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_login()
    {
        $user = User::factory()->create([
            'phone_number' => '123456789',
            'password' => bcrypt('password123'),
            'role' => 'parent',
        ]);

        $response = $this->postJson('/api/users/login', [
            'phone_number' => '123456789',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['token']);
    }

    public function test_user_profile()
    {
        $user = User::factory()->create([
            'role' => 'parent',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'profile_picture' => 'profile.jpg'
        ]);
        $parent = Parent::factory()->create([
            'user_id' => $user->id,
            'address' => '123 Main St'
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->getJson('/api/users/profile', [
            'Authorization' => "Bearer $token",
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'user' => ['id', 'user_name', 'phone_number', 'role', 'profile_picture', 'first_name', 'last_name'],
                 ]);
    }

    public function test_get_parent_students()
    {
        $user = User::factory()->create(['role' => 'parent']);
        $parent = Parent::factory()->create(['user_id' => $user->id]);
        $class = Classes::factory()->create([
            'name' => 'Grade 1',
            'academic_year' => '2023-2024'
        ]);
        $student = Student::factory()->create([
            'parent_id' => $parent->id,
            'class_id' => $class->id,
            'first_name' => 'Student',
            'last_name' => 'One',
            'date_of_birth' => '2015-01-01'
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->getJson('/api/parents/students', [
            'Authorization' => "Bearer $token"
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['id' => $student->id]);
    }

    public function test_update_student_profile()
    {
        $user = User::factory()->create(['role' => 'parent']);
        $parent = Parent::factory()->create(['user_id' => $user->id]);
        $class = Classess::factory()->create();
        $student = Student::factory()->create([
            'parent_id' => $parent->id,
            'class_id' => $class->id
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->putJson("/api/parents/students/{$student->id}", [
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'class_id' => $class->id,
            'profile_picture' => 'new_profile.png',
            'date_of_birth' => '2015-01-01'
        ], [
            'Authorization' => "Bearer $token"
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Profile updated successfully.']);
    }

    public function test_add_course()
    {
        $user = User::factory()->create(['role' => 'instructor']);
        $class = Classess::factory()->create();

        $token = JWTAuth::fromUser($user);

        $response = $this->postJson('/api/courses', [
            'name' => 'Mathematics',
            'class_id' => $class->id,
            'description' => 'Basic math course',
            'instructor_id' => $user->id
        ], [
            'Authorization' => "Bearer $token"
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Course added successfully.']);
    }

    public function test_add_result()
    {
        $user = User::factory()->create(['role' => 'instructor']);
        $class = Classess::factory()->create();
        $student = Student::factory()->create(['class_id' => $class->id]);
        $course = Course::factory()->create([
            'class_id' => $class->id,
            'instructor_id' => $user->id
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->postJson('/api/results', [
            'student_id' => $student->id,
            'course_id' => $course->id,
            'semester' => 'Spring 2024',
            'type' => 'quiz',
            'title' => 'Math Test',
            'date' => now()->format('Y-m-d'),
            'score' => 95.0,
            'total' => 100,
            'comment' => 'Excellent work'
        ], [
            'Authorization' => "Bearer $token"
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Result added successfully.']);
    }

    public function test_mark_attendance()
    {
        $user = User::factory()->create(['role' => 'instructor']);
        $class = Classess::factory()->create();
        $student = Student::factory()->create(['class_id' => $class->id]);

        $token = JWTAuth::fromUser($user);

        $response = $this->postJson('/api/attendance', [
            'student_id' => $student->id,
            'date' => now()->format('Y-m-d'),
            'status' => 'absent',
            'reason' => 'Sick',
            'notes' => 'Doctor\'s note provided'
        ], [
            'Authorization' => "Bearer $token"
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Attendance marked successfully.']);
    }
}