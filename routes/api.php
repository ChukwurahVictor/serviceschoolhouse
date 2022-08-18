<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CoursesController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportingController;
use App\Http\Controllers\SiteAdminController;
use App\Http\Controllers\UserController;
// use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

// Auth Controller Endpoints
Route::prefix("v1")->group(function () {
    Route::post("/signup", [AuthController::class, "signup"]);
    Route::post("/login", [AuthController::class, "login"]);
    Route::get("/verifyemail/{token}", [AuthController::class, "verifyEmail"]);
    Route::post("/forgot-password", [AuthController::class, "forgotPassword"]);
    Route::post("/update-forgot-password", [AuthController::class, "updateForgotPassword"]);
    
});

// User Controller Endpoints
Route::post("/v1/testCMail", [UserController::class, "testCMail"]); 
Route::post("/v1/test-create-email", [UserController::class, "testmailCreate"]);
Route::prefix("v1")->middleware("isAdmin")->group(function () {
    Route::post("/user", [UserController::class, "createCompanyUser"]);
    Route::put("/edit-user", [UserController::class, "editCompanyUser"]);
    Route::delete("/delete-user", [UserController::class, "deleteCompanyUser"]);
    Route::post("/companyusers", [UserController::class, "getCompanyUsers"]);
    Route::post("/company-users-search", [UserController::class, "companyUserSearch"]);
    Route::post("/bulk-upload", [UserController::class, "bulkUpload"]);
    
    
});

// Course Controller Endpoints
Route::prefix("v1")->group(function () {
    Route::get("/course", [CoursesController::class, "getCourses"]);
    Route::post("/course-enrollment", [CoursesController::class, "enrolToCourse"]);
    Route::delete("/course-enrollment", [CoursesController::class, "unEnrolFromCourse"]);
    Route::post("/company-enrollment", [CoursesController::class, "enrolCompanyToCourse"]);
    Route::post("/enrolled-courses", [CoursesController::class, "getEnrolledCourses"]);
    Route::post("/enrolled-course-users", [CoursesController::class, "getEnrolledCourseUsers"]);
    Route::post("/modules-topics", [CoursesController::class, "getCourseModuleTopics"]);
    Route::post("/modules-loggedIn", [CoursesController::class, "getCourseModulesForLoggedInUsers"]);
    Route::post("/course-seats", [CoursesController::class, "getCourseSeats"]);
    Route::post("/assignment-courses", [CoursesController::class, "getCoursesAssignment"]);
    Route::post("/course-trackerLog", [CoursesController::class, "getCourseTrackerLog"]);
    Route::post("/course-progress", [CoursesController::class, "insertCourseTracker"]);
    Route::post("/assessment-progress", [CoursesController::class, "insertAssessmentTracker"]);
    Route::get("/course-tracker/{token}", [CoursesController::class, "courseTrackerLog"]);
    Route::post("/test-assign-email", [CoursesController::class, "testmailAssign"]);
   
});

// Group Controller Endpoints
Route::prefix("v1")->group(function () {
    Route::post("/group", [GroupController::class, "createGroup"]);
    Route::put("/group", [GroupController::class, "editGroup"]);
    Route::delete("/group", [GroupController::class, "removeGroup"]);
    Route::get("/groups-company/{token}", [GroupController::class, "fetchCompanyGroup"]);
    Route::post("/group-course", [GroupController::class, "assignCourse"]);
    Route::delete("/group-course", [GroupController::class, "unassignCourse"]);
    Route::post("/courses-group", [GroupController::class, "fetchGroupCourse"]);
    Route::post("/group-user", [GroupController::class, "addUser"]);
    Route::delete("/group-user", [GroupController::class, "removeUser"]);
    Route::post("/users-group", [GroupController::class, "fetchGroupUser"]);
    Route::post("/sum-price", [GroupController::class, "sumPrice"]);
    Route::post("/test-email", [GroupController::class, "testMail"]);
});

// Profile Page Controller Endpoints
Route::prefix("v1")->group(function () {
    Route::get("/profile/{token}", [ProfileController::class, "getUserDetails"]);
    Route::post("/profile/{token}", [ProfileController::class, "updateUserDetails"]);
    Route::post("/company-profile/{token}", [ProfileController::class, "updateCompanyDetails"]);
    Route::post("/password/{token}", [ProfileController::class, "updatePassword"]);
});

// Order Page Controller Endpoints
Route::prefix("v1")->group(function () {
    Route::get("/orders/{token}", [OrderController::class, "getOrders"]);
    Route::post("/orders", [OrderController::class, "checkoutOrders"])->middleware("isAdmin");
});

// Site Admin Page Controller Endpoints
Route::prefix("v1")->middleware("isSiteAdmin")->group(function () {
    Route::post("/registered-companies", [SiteAdminController::class, "getCompanies"]);
    Route::post("/registered-users", [SiteAdminController::class, "getUsers"]);
    Route::put("/admin-course", [SiteAdminController::class, "editCourse"])->withoutMiddleware("isSiteAdmin");
    Route::delete("/admin-company", [SiteAdminController::class, "deleteCompany"]);
    Route::post("/admin-course", [SiteAdminController::class, "createCourse"]);
    Route::post("/admin-login", [SiteAdminController::class, "adminLogin"]);
    Route::delete("/admin-course", [SiteAdminController::class, "deleteCourse"]);
    Route::put("/admin-company", [SiteAdminController::class, "editCompany"]);
    Route::post("/admin-publish", [SiteAdminController::class, "publish"]);
    Route::post("/admin-module", [SiteAdminController::class, "addModule"]);
    Route::put("/module-sum", [SiteAdminController::class, "sumOfModule"]);
    Route::put("/admin-module", [SiteAdminController::class, "editModule"]);
    Route::delete("/admin-module", [SiteAdminController::class, "deleteModule"]);
    Route::post("/admin-bundle", [SiteAdminController::class, "addBundle"]);
    Route::post("/bundle-courses", [SiteAdminController::class, "coursesInBundles"]);
    Route::put("/admin-bundle", [SiteAdminController::class, "editBundle"]);
    Route::delete("/admin-bundle", [SiteAdminController::class, "deleteBundle"]);
    Route::post("/admin-order", [SiteAdminController::class, "getOrders"]);
    Route::put("/admin-order", [SiteAdminController::class, "editOrderStatus"]);
    Route::post("/add-course", [SiteAdminController::class, "addCourseToOrders"]);
    Route::post("/assign-course",[SiteAdminController::class, "assignCoursesToCompany"]);
    Route::get("/scores-and-attempts",[SiteAdminController::class, "getCandidatesScores"]);
    Route::get("/course-deadline",[SiteAdminController::class, "getDeadline"]);
    Route::post("/add-to-wishlist",[SiteAdminController::class, "addToWishList"]);
    // Route::post("/admin-topic", [SiteAdminController::class, "addTopic"]);
    Route::post("/test-upload", [SiteAdminController::class, "testFileUpload"])->withoutMiddleware("isSiteAdmin");
    Route::post("/test-folderupload", [SiteAdminController::class, "testFolderUpload"])->withoutMiddleware("isSiteAdmin");
});

//Reporting Controller
Route::prefix("v1")->middleware("isAdmin")->group(function () {
    Route::post("/all-courses", [ReportingController::class, "allCourses"]);
    Route::post("/filters", [ReportingController::class, "filterParams"]);
    Route::post("/module-users", [ReportingController::class, "courseModuleUsers"]);
    Route::post("/candidate-table", [ReportingController::class, "candidateTable"]);
    Route::get("/search-candidate", [ReportingController::class, "searchCandidate"]);
    Route::post("/course-view",[ReportingController::class, "courseView"]);
});