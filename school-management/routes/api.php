<?php
//rout
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;
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


Route::prefix('users')->group(function () {
    Route::post('/login', [UserController::class, 'login']);
    Route::post('/reset_password', [UserController::class, 'resetPassword']);
    Route::get('/profile', [UserController::class, 'getProfile'])->middleware('auth:sanctum');
    Route::post('/register', [UserController::class, 'register']);

});

Route::prefix('parents')->group(function () {
    Route::get('/students', [ParentController::class, 'getStudents'])->middleware('auth:sanctum');
    Route::put('/students/{student_id}', [ParentController::class, 'updateStudentProfile']);
});

Route::prefix('students')->group(function () {
    Route::get('/{student_id}', [StudentController::class, 'getProfile']);
});

Route::prefix('instructors')->group(function () {
    Route::get('/classes', [InstructorController::class, 'getClasses'])->middleware('auth:sanctum');
    Route::post('/communicate', [InstructorController::class, 'communicateWithParents']);
    Route::post('/attendance', [InstructorController::class, 'markAttendance']);
});

Route::prefix('courses')->group(function () {
    Route::post('/', [CourseController::class, 'addCourse']);
    Route::put('/{course_id}', [CourseController::class, 'updateCourse']);
});

Route::prefix('results')->group(function () {
    Route::post('/', [ResultController::class, 'addResult']);
    Route::put('/{result_id}', [ResultController::class, 'updateResult']);
});

Route::prefix('attendance')->group(function () {
    Route::post('/', [AttendanceController::class, 'markAttendance']);
    Route::get('/{student_id}', [AttendanceController::class, 'viewAttendance']);
});

Route::prefix('timetable')->group(function () {
    Route::get('/{class_id}', [TimetableController::class, 'viewTimetable']);
    Route::put('/{timetable_id}', [TimetableController::class, 'updateTimetable']);
});

Route::prefix('events')->group(function () {
    Route::post('/', [EventController::class, 'addEvent']);
    Route::get('/', [EventController::class, 'viewEvents']);
});

Route::prefix('gallery')->group(function () {
    Route::post('/', [GalleryController::class, 'addImage']);
    Route::get('/{event_id}', [GalleryController::class, 'viewGallery']);
});

Route::prefix('notices')->group(function () {
    Route::post('/', [NoticeController::class, 'addNotice']);
    Route::get('/', [NoticeController::class, 'viewNotices']);
});

Route::prefix('library')->group(function () {
    Route::post('/', [LibraryController::class, 'addBook']);
    Route::get('/', [LibraryController::class, 'viewBooks']);
    Route::get('/search', [LibraryController::class, 'searchBooks']);
    Route::get('/download/{book_id}', [LibraryController::class, 'downloadBook']);
});

Route::prefix('messages')->group(function () {
    Route::post('/', [MessageController::class, 'sendMessage']);
    Route::get('/{user_id}', [MessageController::class, 'viewMessages']);
});
