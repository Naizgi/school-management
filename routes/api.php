<?php

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\ClassController;
use App\Http\Controllers\API\ParentController;
use App\Http\Controllers\API\StudentController;
use App\Http\Controllers\API\InstructorController;
use App\Http\Controllers\API\CourseController;
use App\Http\Controllers\API\ResultController;
use App\Http\Controllers\API\AttendanceController;
use App\Http\Controllers\API\TimetableController;
use App\Http\Controllers\API\EventController;
use App\Http\Controllers\API\GalleryController;
use App\Http\Controllers\API\NoticeController;
use App\Http\Controllers\API\LibraryController;
use App\Http\Controllers\API\MessageController;

// 🟢 Public Routes (No authentication required)
Route::prefix('users')->group(function () {
    Route::post('/login', [UserController::class, 'login']);
    Route::post('/register', [UserController::class, 'register']);
    Route::post('/bulk-register', [UserController::class, 'bulkRegister']);

});

// 🔴 Protected Routes (Require JWT authentication)

    
    // User Routes
    Route::prefix('users')->group(function () {
        Route::post('/logout', [UserController::class, 'logout']);
        Route::get('/profile', [UserController::class, 'getProfile']);
    });
 
    // Parent Routes
    Route::prefix('parents')->group(function () {
      
        Route::post('/register-student', [ParentController::class, 'registerStudent']);
        Route::get('/students', [ParentController::class, 'getStudents']);
        Route::put('/students/{student_id}', [ParentController::class, 'updateStudentProfile']);
    });

    // Student Routes
    Route::prefix('students')->group(function () {
        Route::get('/{student_id}', [StudentController::class, 'getProfile']);
    });

    Route::prefix('classes')->group(function () {
        Route::get('/index', [ClassController::class, 'index']);
    Route::post('/store', [ClassController::class, 'store']);
    Route::get('/show/{id}', [ClassController::class, 'show']);
    Route::put('/update/{id}', [ClassController::class, 'update']);
    Route::delete('/destroy/{id}', [ClassController::class, 'destroy']);
    Route::get('/{id}/statistics', [ClassController::class, 'statistics']);
    Route::get('/{id}/students', [ClassController::class, 'students']);
    });

    // Instructor Routes
    Route::prefix('instructors')->group(function () {
        Route::get('/classes', [InstructorController::class, 'getClasses']);
        Route::post('/communicate', [InstructorController::class, 'communicateWithParents']);
        Route::post('/attendance', [InstructorController::class, 'markAttendance']);
    });

    // Course Routes
    Route::prefix('courses')->group(function () {
        // Public routes (if any)
        Route::get('/viewall', [CourseController::class, 'viewCourses']);
        Route::get('/view/{id}', [CourseController::class, 'viewCourse']);
        
        // Protected routes (require admin/instructor role)
   
            Route::post('/add', [CourseController::class, 'addCourse']);
            Route::put('/update/{course_id}', [CourseController::class, 'updateCourse']);
            // Future protected routes can be added here
      
    });
    // Results Routes
    Route::prefix('results')->group(function () {
        Route::post('/', [ResultController::class, 'addResult']);
        Route::put('/{result_id}', [ResultController::class, 'updateResult']);
    });

    // Attendance Routes
    Route::prefix('attendance')->group(function () {
        Route::post('/', [AttendanceController::class, 'markAttendance']);
        Route::get('/{student_id}', [AttendanceController::class, 'viewAttendance']);
    });

    // Timetable Routes
    Route::prefix('timetable')->group(function () {
        Route::get('/{class_id}', [TimetableController::class, 'viewTimetable']);
        Route::put('/{timetable_id}', [TimetableController::class, 'updateTimetable']);
    });

    // Event Routes
    Route::prefix('events')->group(function () {
        Route::post('/', [EventController::class, 'addEvent']);
        Route::get('/', [EventController::class, 'viewEvents']);
    });

    // Gallery Routes
    Route::prefix('gallery')->group(function () {
        Route::post('/', [GalleryController::class, 'addImage']);
        Route::get('/{event_id}', [GalleryController::class, 'viewGallery']);
    });

    // Notice Routes
    Route::prefix('notices')->group(function () {
        Route::post('/', [NoticeController::class, 'addNotice']);
        Route::get('/', [NoticeController::class, 'viewNotices']);
    });

    // Library Routes
    Route::prefix('library')->group(function () {
        Route::post('/', [LibraryController::class, 'addBook']);
        Route::get('/', [LibraryController::class, 'viewBooks']);
        Route::get('/search', [LibraryController::class, 'searchBooks']);
        Route::get('/download/{book_id}', [LibraryController::class, 'downloadBook']);
    });

    // Message Routes
    Route::prefix('messages')->group(function () {
        Route::post('/', [MessageController::class, 'sendMessage']);
        Route::get('/{user_id}', [MessageController::class, 'viewMessages']);
    });




    Route::middleware('auth:api')->group(function () {

});
