<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Parentmodel;
use App\Models\Student;
use App\Models\Course;
use App\Models\Result;
use App\Models\Attendance;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_tests_user_login()
    {
        $user = User::factory()->create([
            'user_name' => 'johndoe',
            'phone_number' => '123456789',
            'password' => bcrypt('password'),
            'role' => 'Parent',
            'first_name' => 'John',
            'last_name' => 'Doe'
        ]);

        $response = $this->postJson('/api/users/login', [
            'user_name' => 'johndoe',
            'phone_number' => '123456789',
            'password' => 'password'
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['token']);
    }

    /** @test */
    public function it_tests_user_profile()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/users/profile');

        $response->assertStatus(200)
                 ->assertJson(['user_name' => $user->user_name]);
    }

    /** @test */
    public function it_tests_get_parent_students()
    {
        $user = User::factory()->create(['role' => 'Parent']);
        $parent = Parent::factory()->create(['user_id' => $user->user_id]);
        $student = Student::factory()->create(['parent_id' => $parent->parent_id]);

        $this->actingAs($user, 'sanctum');
        $response = $this->getJson('/api/parents/students');

        $response->assertStatus(200)
                 ->assertJsonFragment(['student_id' => $student->student_id]);
    }

    /** @test */
    public function it_tests_update_student_profile()
    {
        $user = User::factory()->create(['role' => 'Parent']);
        $parent = Parent::factory()->create(['user_id' => $user->user_id]);
        $student = Student::factory()->create(['parent_id' => $parent->parent_id]);

        $this->actingAs($user, 'sanctum');

        $response = $this->putJson("/api/parents/students/{$student->student_id}", [
            'name' => 'Updated Name',
            'class_id' => 1,
            'profile_picture' => 'new_profile.png'
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Profile updated successfully.']);
    }

    /** @test */
    public function it_tests_add_course()
    {
        $response = $this->postJson('/api/courses', [
            'course_name' => 'Mathematics',
            'class_id' => 1
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Course added successfully.']);
    }

    /** @test */
    public function it_tests_add_result()
    {
        $student = Student::factory()->create();
        $course = Course::factory()->create();

        $response = $this->postJson('/api/results', [
            'student_id' => $student->student_id,
            'course_id' => $course->course_id,
            'semester' => 'Spring 2024',
            'activity_type' => 'Quiz',
            'title' => 'Math Test',
            'date' => now()->format('Y-m-d'),
            'score' => 95.0,
            'amount' => 0
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Result added successfully.']);
    }

    /** @test */
    public function it_tests_mark_attendance()
    {
        $student = Student::factory()->create();

        $response = $this->postJson('/api/attendance', [
            'student_id' => $student->student_id,
            'date_of_absence' => now()->format('Y-m-d'),
            'reason' => 'Sick'
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Attendance marked successfully.']);
    }
}
