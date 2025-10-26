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
use App\Http\Controllers\API\TimeSlotController;
use App\Http\Controllers\API\EventController;
use App\Http\Controllers\API\GalleryController;
use App\Http\Controllers\API\NoticeController;
use App\Http\Controllers\API\LibraryController;
use App\Http\Controllers\API\MessageController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\ActivityTypeController;
use App\Http\Controllers\API\CourseAssignmentController;







// 🟢 Public Routes (No authentication required)
Route::prefix('users')->group(function () {
    Route::post('/login', [UserController::class, 'login']);
    Route::post('/register', [UserController::class, 'register']);
    Route::post('/bulk-register', [UserController::class, 'bulkRegister']);

});




// 🔴 Protected Routes (Require JWT authentication) keseran bewhala enastekakalewalen man lelawn mokrew yaredo


 Route::middleware('auth:api')->group(function () {

    // User Routes
    Route::prefix('users')->group(function () {
        Route::post('/logout', [UserController::class, 'logout']);
        Route::get('/profile', [UserController::class, 'getProfile']);
        Route::put('/profile', [UserController::class, 'updateProfile']);
        Route::post('/profile/upload-photo', [UserController::class, 'uploadProfilePicture']);
        Route::post('/change-password', [UserController::class, 'changePassword']);
    });
 
Route::prefix('dashboard')->group(function () {
    Route::middleware('auth:api')->get('/summary', [DashboardController::class, 'summary']);

    Route::get('/attendance-summary', [DashboardController::class, 'attendanceSummary']);
    Route::get('/notices', [DashboardController::class, 'notices']);
    Route::get('/events', [DashboardController::class, 'events']);
});



    // Parent Routes
   // Parent Student Management Routes
Route::prefix('parent')->group(function () {
    // Student Registration
    Route::post('/register-student', [ParentController::class, 'registerStudent']); // Single student registration
    Route::post('/bulk-register-students', [ParentController::class, 'bulkRegisterStudents']); // Bulk student registration
    
    // Student Management
    Route::get('/students', [ParentController::class, 'getStudents']); // Get all students for parent
    Route::put('/students/{student_id}', [ParentController::class, 'updateStudentProfile']); // Update student profile
    Route::post('/students/{student_id}/upload-photo', [ParentController::class, 'uploadProfilePicture']); // Upload profile picture
});

    // Student Routes
    Route::prefix('students')->group(function () {
       Route::get('/students/{id}', [StudentController::class, 'getStudentDetails']); // Complete details
       Route::get('/student/profile/{student_id}', [StudentController::class, 'getProfile']); // Basic profile
       Route::get('/students/list', [StudentController::class, 'listStudents']); // List with pagination
       Route::get('/students/search', [StudentController::class, 'searchStudents']);
       Route::get('/students/advanced-search', [StudentController::class, 'advancedSearchStudents']);
       Route::get('/students/suggestions', [StudentController::class, 'getSearchSuggestions']);
       Route::get('/students/by-class-section', [StudentController::class, 'getStudentsByClassAndSection']);
       Route::get('/students/by-grade-section', [StudentController::class, 'getStudentsByGradeAndSection']);
       Route::get('/classes-sections', [StudentController::class, 'getAvailableClassesAndSections']);


     });

    Route::prefix('classes')->group(function () {
        Route::get('/index', [ClassController::class, 'index']);
        Route::get('/sections/all', [ClassController::class, 'AllSections']);// hulum section nw
        Route::post('/sections/bulk', [ClassController::class, 'bulkManageSections']);//bulk section register yadergal
       Route::get('/class/sections/bygrade/{grade}', [ClassController::class, 'getSectionsByGrade']);//grade tetekmo class yasayal
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
        Route::get('/instructors', [InstructorController::class, 'listInstructors']);
    });

    // Course Routes
    Route::prefix('courses')->group(function () {
        // Public routes (if any)
        Route::get('/viewall', [CourseController::class, 'viewCourses']);
        Route::get('/view/{id}', [CourseController::class, 'viewCourse']);
        Route::get('/list', [CourseController::class, 'listCourses']);
        Route::post('/bulk', [CourseController::class, 'bulkManageCourses']);
        Route::get('/by/grade/{gradeId}', [CourseController::class, 'getCoursesByGrade']);



        // Protected routes (require admin/instructor role)
   
            Route::post('/add', [CourseController::class, 'addCourse']);
            Route::put('/update/{course_id}', [CourseController::class, 'updateCourse']);
            Route::get('/class/{class_id}', [CourseController::class, 'getCoursesByClassId']);
            Route::delete('/delete/{id}', [CourseController::class, 'deleteCourse']);


            // Future protected routes can be added here
      
    });
    // Results Routes
    Route::prefix('results')->group(function () {
        Route::post('/add', [ResultController::class, 'addResult']);
        Route::put('/update/{result_id}', [ResultController::class, 'updateResult']);
        Route::get('/fetch', [ResultController::class, 'fetchResultByStudentAndCourse']);
        Route::get('/fetch-all', [ResultController::class, 'fetchAllResultsByStudentAndCourse']);
        Route::get('/activity-types', [ResultController::class, 'getActivityTypes']);

    });

    // Attendance Routes
    Route::prefix('attendance')->group(function () {
           // Student-specific attendance routes
          Route::get('/students/{student_id}/attendance', [AttendanceController::class, 'viewAttendance']);
          Route::get('/students/{student_id}/attendance/stats', [AttendanceController::class, 'getStudentAttendanceStats']);
          Route::get('/students/{student_id}/attendance/monthly-summary', [AttendanceController::class, 'getStudentMonthlySummary']);
          Route::get('/students/{student_id}/attendance/calendar', [AttendanceController::class, 'getStudentAttendanceCalendar']);
    
    // General attendance routes
          Route::post('/attendance', [AttendanceController::class, 'markAttendance']);
          Route::post('/attendance/bulk', [AttendanceController::class, 'markAttendanceBulk']);
          Route::put('/attendance/{attendance_id}', [AttendanceController::class, 'updateAttendance']);
          Route::delete('/attendance/{attendance_id}', [AttendanceController::class, 'deleteAttendance']);
    
    // Class-based attendance routes (keep your existing ones)
         Route::get('/attendance/class/{class_id}', [AttendanceController::class, 'viewAttendanceByClassId']);
    });

    // Timetable Routes
    Route::prefix('timeslots')->group(function () {
        Route::post('/', [TimeSlotController::class, 'store']);         // Store new timeslot
        Route::get('/', [TimeSlotController::class, 'index']);          // Get all timeslots
        Route::get('/{id}', [TimeSlotController::class, 'show']);       // Show single timeslot by ID
        Route::put('/{id}', [TimeSlotController::class, 'update']);     // Update timeslot by ID
        Route::delete('/{id}', [TimeSlotController::class, 'destroy']); // Delete timeslot by ID
    });

    Route::prefix('timetable')->group(function () {
        Route::post('/timetable', [TimetableController::class, 'store']);
        Route::post('/timetable/weekly', [TimetableController::class, 'createWeeklyTimetable']);
        Route::get('/timetable/class/{class_id}', [TimetableController::class, 'viewTimetable']);
        Route::get('/timetable/class/{class_id}/day/{day}', [TimetableController::class, 'viewTimetableByDay']);
        Route::put('/timetable/{timetable_id}', [TimetableController::class, 'updateTimetable']);
        Route::delete('/timetable/{timetable_id}', [TimetableController::class, 'destroy']);
        Route::get('/timeslots', [TimetableController::class, 'getAvailableTimeslots']);
        Route::post('/timetable/check-conflicts', [TimetableController::class, 'checkConflicts']);
        Route::get('/timetable/teacher/{teacher_id}', [TimetableController::class, 'getTeacherTimetable']);
    });



    // Event Routes
    Route::prefix('events')->group(function () {
        Route::post('/', [EventController::class, 'addEvent']);
        Route::get('/', [EventController::class, 'viewEvents']);
    });

    Route::prefix('activity')->group(function () {
         Route::apiResource('activity-types', ActivityTypeController::class);
         Route::get('activity-types/grade/{grade}', [ActivityTypeController::class, 'index']);
         Route::get('activity-types/semester/{semester}', [ActivityTypeController::class, 'index']);
    });

    // Gallery Routes
    Route::prefix('gallery')->group(function () {
        Route::post('/', [GalleryController::class, 'addImage']);
        Route::get('/{event_id}', [GalleryController::class, 'viewGallery']);
        Route::get('/latest', [GalleryController::class, 'latestImages']);
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
         Route::post('/messages/send', [MessageController::class, 'sendMessage']);
         Route::get('/messages/chat-history', [MessageController::class, 'getChatHistory']);
         Route::get('/messages/with-user/{user_id}', [MessageController::class, 'getMessagesWithUser']);
         Route::post('/messages/mark-read', [MessageController::class, 'markAsRead']);
         Route::post('/messages/mark-all-read/{user_id}', [MessageController::class, 'markAllAsReadFromUser']);
         Route::get('/messages/unread-count', [MessageController::class, 'getUnreadCount']);
         Route::delete('/messages/{message_id}', [MessageController::class, 'deleteMessage']);
         Route::get('/messages/search', [MessageController::class, 'searchMessages']);
    });

 Route::prefix('course-assign')->group(function () {
         Route::post('/course-assignments', [CourseAssignmentController::class, 'assignCourse']);
         Route::post('/course-assignments/bulk', [CourseAssignmentController::class, 'bulkAssignCourses']);
         Route::get('/course-assignments/class/{class_id}', [CourseAssignmentController::class, 'getAssignmentsByClass']);
         Route::get('/course-assignments/instructor/{instructor_id}', [CourseAssignmentController::class, 'getInstructorAssignments']);
         Route::get('/course-assignments/available/{class_id}', [CourseAssignmentController::class, 'getAvailableCourses']);
         Route::put('/course-assignments/{assignment_id}', [CourseAssignmentController::class, 'updateAssignment']);
         Route::delete('/course-assignments/{assignment_id}', [CourseAssignmentController::class, 'deleteAssignment']);
         Route::get('/course-assignments/classes', [CourseAssignmentController::class, 'getAllClassesWithAssignments']);
    });


   

});
